<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Indexer\Changelog\CollectionFactory;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\EntityType;
use Infrangible\Core\Helper\Models;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Zend_Db_Select;
use Zend_Db_Select_Exception;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ChangeLog
{
    /** @var array */
    public static $priceAttributes = [
        'price',
        'special_price',
        'special_from_date',
        'special_to_date',
        'group_price',
        'tier_price'
    ];

    /** @var Stores */
    protected $storeHelper;

    /** @var Database */
    protected $databaseHelper;

    /** @var \Infrangible\Core\Helper\Product */
    protected $productHelper;

    /** @var Attribute */
    protected $attributeHelper;

    /** @var EntityType */
    protected $eavEntityTypeHelper;

    /** @var Data */
    protected $productFeedHelper;

    /** @var LoggerInterface */
    protected $logging;

    /** @var Type */
    protected $productType;

    /** @var CollectionFactory */
    protected $changeLogCollectionFactory;

    /** @var Variables */
    protected $variables;

    /** @var Models */
    protected $modelHelper;

    /** @var Arrays */
    protected $arrays;

    /** @var array */
    private $changedAttributeCodes = [];

    public function __construct(
        Stores $storeHelper,
        Database $databaseHelper,
        \Infrangible\Core\Helper\Product $catalogProductHelper,
        Attribute $catalogAttributeHelper,
        EntityType $eavEntityTypeHelper,
        Data $productFeedHelper,
        LoggerInterface $logging,
        Type $productType,
        CollectionFactory $changeLogCollectionFactory,
        Variables $variables,
        Models $modelHelper,
        Arrays $arrays
    ) {
        $this->storeHelper = $storeHelper;
        $this->databaseHelper = $databaseHelper;
        $this->productHelper = $catalogProductHelper;
        $this->attributeHelper = $catalogAttributeHelper;
        $this->eavEntityTypeHelper = $eavEntityTypeHelper;
        $this->productFeedHelper = $productFeedHelper;
        $this->logging = $logging;
        $this->productType = $productType;
        $this->changeLogCollectionFactory = $changeLogCollectionFactory;
        $this->variables = $variables;
        $this->modelHelper = $modelHelper;
        $this->arrays = $arrays;
    }

    /**
     * @throws LocalizedException
     */
    public function insertEntityIds(array $entityIds, string $entityTypeCode)
    {
        $entityType = $this->eavEntityTypeHelper->getEntityType($entityTypeCode);

        $date = gmdate('Y-m-d H:i:s');

        $row = [
            'entity_type_id' => $entityType->getEntityTypeId(),
            'created_at'     => $date,
            'updated_at'     => $date
        ];

        $data = [];

        foreach ($entityIds as $entityId) {
            $row[ 'entity_id' ] = $entityId;

            $data[] = $row;
        }

        $collection = $this->changeLogCollectionFactory->create();

        $collection->getConnection()->insertOnDuplicate(
            $collection->getMainTable(),
            $data,
            ['updated_at']
        );
    }

    /**
     * @throws Zend_Db_Select_Exception
     * @throws LocalizedException
     * @throws Exception
     */
    public function fetchEntityIds(string $entityTypeCode, bool $remove = false): array
    {
        $entityType = $this->eavEntityTypeHelper->getEntityType($entityTypeCode);

        $collection = $this->changeLogCollectionFactory->create();

        $collection->addEntityTypeIdFilter($this->variables->intValue($entityType->getEntityTypeId()));

        $collection->load();

        if ($remove === true) {
            $collection->getConnection()->delete(
                $collection->getMainTable(),
                $collection->getSelect()->getPart(Zend_Db_Select::WHERE)
            );
        }

        return $collection->getColumnValues('entity_id');
    }

    /**
     * @throws Zend_Db_Select_Exception
     */
    public function removeEntityIds(string $date)
    {
        $collection = $this->changeLogCollectionFactory->create();

        $collection->addFieldToFilter(
            'created_at',
            ['lt' => $date]
        );

        $collection->getConnection()->delete(
            $collection->getMainTable(),
            $collection->getSelect()->getPart(Zend_Db_Select::WHERE)
        );
    }

    public function isPriceUpdateRequired(Product $product): bool
    {
        return ! $this->variables->isEmpty(
            array_intersect(
                static::$priceAttributes,
                $this->getChangedAttributeCodes($product)
            )
        );
    }

    public function isAttributeUpdateRequired(Product $product): bool
    {
        foreach ($this->storeHelper->getStores(true) as $store) {
            try {
                $allEavAttributes = $this->productFeedHelper->getAllEavAttributes(
                    Data::MODE_DELTA,
                    $this->variables->intValue($store->getId())
                );

                foreach ($allEavAttributes as $eavAttribute) {
                    $eavAttributeCode = $eavAttribute->getAttributeCode();

                    if (! in_array(
                            $eavAttributeCode,
                            ['created_at', 'updated_at']
                        ) && $product->dataHasChangedFor($eavAttributeCode)) {

                        return true;
                    }
                }
            } catch (Exception $exception) {
                $this->logging->error($exception);
            }
        }

        return false;
    }

    public function isStockUpdateRequired(Product $product): bool
    {
        $quantityAndStockStatus = $product->getData('quantity_and_stock_status');
        $origQuantityAndStockStatus = $product->getOrigData('quantity_and_stock_status');

        if (is_array($quantityAndStockStatus) && is_array($origQuantityAndStockStatus)) {
            $isInStock = boolval(
                $this->arrays->getValue(
                    $quantityAndStockStatus,
                    'is_in_stock',
                    false
                )
            );

            $origIsInStock = boolval(
                $this->arrays->getValue(
                    $origQuantityAndStockStatus,
                    'is_in_stock',
                    false
                )
            );

            return $isInStock !== $origIsInStock;
        }

        return false;
    }

    public function getChangedAttributeCodes(Product $product): array
    {
        $productId = $product->getId();

        if (! array_key_exists(
            $productId,
            $this->changedAttributeCodes
        )) {
            $changedAttributeCodes = $this->modelHelper->getChangedAttributeCodes($product);

            $newData = $product->getData();

            if (array_key_exists(
                    'affected_category_ids',
                    $newData
                ) && ! empty($newData[ 'affected_category_ids' ])) {
                $changedAttributeCodes[] = 'category_ids';
            }

            $this->changedAttributeCodes[ $productId ] = array_values(array_unique($changedAttributeCodes));
        }

        return $this->changedAttributeCodes[ $productId ];
    }

    /**
     * @throws Exception
     */
    public function collectProductIds(int $productId): array
    {
        $productIds = [$productId];

        if (! $this->isProductComposite($productId)) {
            $productIds = array_merge(
                $productIds,
                $this->productHelper->getParentIds(
                    $this->databaseHelper->getDefaultConnection(),
                    [$productId],
                    false,
                    false
                )
            );
        }

        return array_unique($productIds);
    }

    /**
     * @throws Exception
     */
    protected function isProductComposite(int $productId): bool
    {
        $typeId = $this->attributeHelper->getAttributeValue(
            $this->databaseHelper->getDefaultConnection(),
            Product::ENTITY,
            'type_id',
            $productId,
            0
        );

        $compositeTypeIndex = array_flip($this->productType->getCompositeTypes());

        return isset($compositeTypeIndex[ $typeId ]);
    }
}
