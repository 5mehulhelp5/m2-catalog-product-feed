<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Task;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Files;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\ChangeLog;
use Infrangible\CatalogProductFeed\Helper\Data;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\CollectionFactory;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Export;
use Infrangible\Core\Helper\Registry;
use Infrangible\Core\Helper\Stores;
use Infrangible\SimpleMail\Model\MailFactory;
use Infrangible\Task\Model\RunFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\UniversalFactory;
use Psr\Log\LoggerInterface;
use Zend_Db_Select_Exception;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Delta extends Base
{
    /** @var ChangeLog */
    protected $productFeedChangeLogHelper;

    public function __construct(
        Files $files,
        Registry $registryHelper,
        \Infrangible\Task\Helper\Data $helper,
        LoggerInterface $logging,
        DirectoryList $directoryList,
        RunFactory $runFactory,
        \Infrangible\Task\Model\ResourceModel\RunFactory $runResourceFactory,
        MailFactory $mailFactory,
        Variables $variables,
        Stores $storeHelper,
        Database $databaseHelper,
        Arrays $arrayHelper,
        Export $catalogExportHelper,
        Data $productFeedHelper,
        CollectionFactory $attributeCollectionFactory,
        \Infrangible\CatalogIndexerFlat\Helper\Data $catalogIndexerFlatHelper,
        UniversalFactory $universalFactory,
        ManagerInterface $eventManager,
        ChangeLog $productFeedChangeLogHelper
    ) {
        parent::__construct(
            $files,
            $registryHelper,
            $helper,
            $logging,
            $directoryList,
            $runFactory,
            $runResourceFactory,
            $mailFactory,
            $variables,
            $storeHelper,
            $databaseHelper,
            $arrayHelper,
            $catalogExportHelper,
            $productFeedHelper,
            $attributeCollectionFactory,
            $catalogIndexerFlatHelper,
            $universalFactory,
            $eventManager
        );

        $this->productFeedChangeLogHelper = $productFeedChangeLogHelper;
    }

    /**
     * @throws LocalizedException
     * @throws Zend_Db_Select_Exception
     * @throws Exception
     */
    protected function runTask(): bool
    {
        $this->logging->info('Starting product feed delta export');

        $entityIds = $this->productFeedChangeLogHelper->fetchEntityIds(
            Product::ENTITY,
            ! $this->isTest()
        );

        $entityIds = array_unique($entityIds);

        if (count($entityIds)) {
            $this->setIsEmptyRun(false);

            $this->reindexProducts(
                Data::MODE_DELTA,
                $this->getStoreCode(),
                $entityIds,
                $this->getIntegrationNames()
            );
        }

        $this->logging->info('Finished product feed delta export');

        return true;
    }
}
