<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Helper;

use Cron\CronExpression;
use DateTime;
use Exception;
use Infrangible\CatalogProductFeed\Model\IIntegration;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\CollectionFactory;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Export;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\Framework\Validator\UniversalFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Data
{
    public const MODE_DELTA = 'delta';
    public const MODE_FULL = 'full';

    /** @var Stores */
    protected $storeHelper;

    /** @var Export */
    protected $catalogExportHelper;

    /** @var Attribute */
    protected $attributeHelper;

    /** @var LoggerInterface */
    protected $logging;

    /** @var UniversalFactory */
    protected $universalFactory;

    /** @var CollectionFactory */
    protected $attributeCollectionFactory;

    /** @var IIntegration[] */
    private $integrations = [];

    public function __construct(
        Stores $storeHelper,
        Export $exportHelper,
        Attribute $attributeHelper,
        LoggerInterface $logging,
        UniversalFactory $universalFactory,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->storeHelper = $storeHelper;
        $this->catalogExportHelper = $exportHelper;
        $this->attributeHelper = $attributeHelper;
        $this->logging = $logging;
        $this->universalFactory = $universalFactory;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    public function getAllIntegrationNames(int $storeId): array
    {
        $integrationsConfig = $this->storeHelper->getStoreConfig(
            'infrangible_catalogproductfeed/integration',
            [],
            false,
            $storeId
        );

        return array_keys($integrationsConfig);
    }

    /**
     * @param string[] $integrationNames
     *
     * @return IIntegration[]
     * @throws Exception
     */
    public function getIntegrations(
        string $useMode,
        int $storeId,
        array $integrationNames,
        ?int $lastRunTime = null
    ): array {
        $integrationsConfig = $this->storeHelper->getStoreConfig(
            'infrangible_catalogproductfeed/integration',
            [],
            false,
            $storeId
        );

        $integrationInstances = [];

        foreach ($integrationsConfig as $integrationName => $integrationClass) {
            if (array_key_exists(
                $integrationName,
                $this->integrations
            )) {
                $integration = $this->integrations[ $integrationName ];
            } else {
                /** @var IIntegration|false $integration */
                $integration = $this->universalFactory->create($integrationClass);

                if ($integration === false) {
                    throw new Exception(
                        sprintf(
                            'Could not load integration class: %s',
                            $integrationClass
                        )
                    );
                }

                $this->integrations[ $integrationName ] = $integration;
            }

            if (! $integration->isEnabled($storeId)) {
                continue;
            }

            if ($useMode === static::MODE_DELTA && ! $integration->useForDelta($storeId)) {
                continue;
            }

            if ($lastRunTime !== null && $integration->isScheduled($storeId)) {
                $isDue = false;

                $checkTimeStamp = $lastRunTime;

                while (! $isDue && $checkTimeStamp <= time()) {
                    $checkTime = new DateTime();

                    $checkTime->setTimestamp($checkTimeStamp);

                    try {
                        $scheduleExpressions = $integration->getScheduleExpression($storeId);

                        foreach ($scheduleExpressions as $scheduleExpression) {
                            if (empty($scheduleExpression)) {
                                continue;
                            }

                            $cronExpression = new CronExpression($scheduleExpression);

                            if ($cronExpression->isDue($checkTime)) {
                                $isDue = true;
                            }
                        }
                    } catch (RuntimeException $exception) {
                        $this->logging->error($exception);

                        break;
                    }

                    $checkTimeStamp += 60;
                }

                if (! $isDue) {
                    continue;
                }
            }

            if (in_array(
                $integrationName,
                $integrationNames
            )) {
                $integrationInstances[ $integrationName ] = $integration;
            }
        }

        return $integrationInstances;
    }

    /**
     * @param IIntegration[] $integrations
     *
     * @throws Exception
     */
    public function getAdditionalEavAttributeCodes(array $integrations): array
    {
        $requiredEavAttributeCodes = $this->getRequiredEavAttributeCodes($integrations);
        $configuredEavAttributeCodes = $this->getConfiguredEavAttributeCodes();

        $additionalEavAttributeCodes = array_merge(
            $requiredEavAttributeCodes,
            $configuredEavAttributeCodes
        );

        return array_unique($additionalEavAttributeCodes);
    }

    /**
     * @param IIntegration[] $integrations
     *
     * @throws Exception
     */
    public function getRequiredEavAttributeCodes(array $integrations): array
    {
        $requiredEavAttributeCodes = [];

        foreach ($integrations as $integration) {
            foreach ($integration->getRequiredEavAttributeCodes() as $requiredEavAttributeCode) {
                $requiredEavAttributeCodes[] = $requiredEavAttributeCode;
            }
        }

        return array_unique($requiredEavAttributeCodes);
    }

    /**
     * @param IIntegration[] $integrations
     */
    public function getAttributeConditions(array $integrations): array
    {
        $attributeConditions = [];

        foreach ($integrations as $integration) {
            $integrationAttributeConditions = $integration->getAttributeConditions();

            foreach ($integrationAttributeConditions as $integrationAttributeCondition) {
                $attributeConditions[] = $integrationAttributeCondition;
            }
        }

        return array_unique($attributeConditions);
    }

    /**
     * @throws Exception
     */
    public function getConfiguredEavAttributeCodes(): array
    {
        $configuredEavAttributeCodes = [];

        $attributeCollection = $this->attributeCollectionFactory->create();

        $attributeCollection->getSelect()->where('eav_attribute_id IS NOT NULL');
        $attributeCollection->getSelect()->where('eav_attribute_id <> ""');
        $attributeCollection->getSelect()->where('eav_attribute_id <> 0');

        /** @var \Infrangible\CatalogProductFeed\Model\Attribute $attribute */
        foreach ($attributeCollection as $attribute) {
            $eavAttribute = $this->attributeHelper->getAttribute(
                Product::ENTITY,
                $attribute->getEavAttributeId()
            );

            $configuredEavAttributeCodes[] = $eavAttribute->getAttributeCode();
        }

        return array_unique($configuredEavAttributeCodes);
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute[]
     * @throws Exception
     */
    public function getAllEavAttributes(string $useMode, int $storeId): array
    {
        $integrationNames = $this->getAllIntegrationNames($storeId);

        $integrations = $this->getIntegrations(
            $useMode,
            $storeId,
            $integrationNames
        );

        return $this->catalogExportHelper->getExportableAttributes([],
            $this->getAdditionalEavAttributeCodes($integrations));
    }
}
