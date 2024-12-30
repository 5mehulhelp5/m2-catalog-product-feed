<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Task;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Files;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\Data;
use Infrangible\CatalogProductFeed\Model\ExportValidation;
use Infrangible\CatalogProductFeed\Model\IIntegration;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\CollectionFactory;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Export;
use Infrangible\Core\Helper\Registry;
use Infrangible\Core\Helper\Stores;
use Infrangible\SimpleMail\Model\MailFactory;
use Infrangible\Task\Model\RunFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Validator\UniversalFactory;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Base extends \Infrangible\Task\Task\Base
{
    /** @var Variables */
    protected $variables;

    /** @var Stores */
    protected $storeHelper;

    /** @var Database */
    protected $databaseHelper;

    /** @var Arrays */
    protected $arrays;

    /** @var Export */
    protected $exportHelper;

    /** @var Data */
    protected $productFeedHelper;

    /** @var CollectionFactory */
    protected $attributeCollectionFactory;

    /** @var \Infrangible\CatalogIndexerFlat\Helper\Data */
    protected $catalogIndexerFlatHelper;

    /** @var UniversalFactory */
    protected $universalFactory;

    /** @var ManagerInterface */
    protected $eventManager;

    /** @var string[] */
    private $integrationNames = [];

    /** @var IIntegration[] */
    private $integrations = [];

    /** @var int[] */
    private $storeIds = [];

    /** @var bool */
    private $force = false;

    /** @var bool */
    private $isEmptyRun = false;

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
        ManagerInterface $eventManager
    ) {
        parent::__construct(
            $files,
            $registryHelper,
            $helper,
            $logging,
            $directoryList,
            $runFactory,
            $runResourceFactory,
            $mailFactory
        );

        $this->variables = $variables;
        $this->storeHelper = $storeHelper;
        $this->databaseHelper = $databaseHelper;
        $this->arrays = $arrayHelper;
        $this->exportHelper = $catalogExportHelper;
        $this->productFeedHelper = $productFeedHelper;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->catalogIndexerFlatHelper = $catalogIndexerFlatHelper;
        $this->universalFactory = $universalFactory;
        $this->eventManager = $eventManager;
    }

    /**
     * @return string[]
     */
    public function getIntegrationNames(): array
    {
        return $this->integrationNames;
    }

    /**
     * @param string[] $integrationNames
     */
    public function setIntegrationNames(array $integrationNames): void
    {
        $this->integrationNames = $integrationNames;
    }

    /**
     * @return int[]
     */
    public function getStoreIds(): array
    {
        return $this->storeIds;
    }

    /**
     * @param int[] $storeIds
     */
    public function setStoreIds(array $storeIds): void
    {
        $this->storeIds = $storeIds;
    }

    public function isForce(): bool
    {
        return $this->force;
    }

    public function setForce(bool $force): void
    {
        $this->force = $force;
    }

    protected function prepare(): void
    {
    }

    /**
     * @throws Exception
     */
    public function reindexProducts(
        string $mode,
        ?string $storeCode = null,
        ?array $productIds = [],
        ?array $integrationNames = [],
        ?int $lastRunTime = null
    ) {
        if ($this->variables->isEmpty($storeCode)) {
            $storeId = null;
        } else {
            $store = $this->storeHelper->getStore($storeCode);
            $storeId = $this->variables->intValue($store->getId());
        }
        if (empty($integrationNames)) {
            $integrationNames = $this->productFeedHelper->getAllIntegrationNames($storeId);
        }

        if (is_null($storeId)) {
            $storeIds = array_keys($this->storeHelper->getStores(true));

            foreach ($storeIds as $storeId) {
                $this->reindexStoreProducts(
                    $mode,
                    $storeId,
                    $productIds,
                    $integrationNames,
                    $lastRunTime
                );
            }
        } else {
            $this->reindexStoreProducts(
                $mode,
                $storeId,
                $productIds,
                $integrationNames,
                $lastRunTime
            );
        }

        foreach ($this->integrations as $integration) {
            $integration->finish($mode);
        }
    }

    /**
     * @throws Exception
     */
    private function reindexStoreProducts(
        string $mode,
        ?int $storeId = null,
        array $productIds = [],
        array $integrationNames = [],
        ?int $lastRunTime = null
    ) {
        $integrations = $this->productFeedHelper->getIntegrations(
            $mode,
            $storeId,
            $integrationNames,
            $lastRunTime
        );

        if (empty($integrations)) {
            return;
        }

        $this->setIsEmptyRun(false);

        foreach ($integrations as $integrationName => $integration) {
            if (! array_key_exists(
                $integrationName,
                $this->integrationNames
            )) {
                $this->integrations[ $integrationName ] = $integration;

                $integration->start(
                    $mode,
                    $lastRunTime
                );
            }
        }

        $this->rebuildStoreIndex(
            $mode,
            $storeId,
            $integrations,
            $productIds
        );
    }

    /**
     * Rebuild index data by product ids
     *
     * @param IIntegration[] $integrations
     */
    private function rebuildStoreIndex(string $mode, int $storeId, array $integrations, array $productIds = [])
    {
        if (php_sapi_name() === 'cli') {
            $this->registryHelper->register(
                'custom_entry_point',
                true,
                true
            );
        }

        foreach ($integrations as $integrationName => $integration) {
            $attributes = $this->attributeCollectionFactory->create();

            $attributes->filterByIntegration($integrationName);

            if ($mode === Data::MODE_DELTA) {
                $attributes->filterDeltaAttributes();
            }

            $attributes->getSelect()->order('position ASC');

            $integration->startStore(
                $mode,
                $storeId,
                $productIds,
                $attributes
            );
        }

        $this->catalogIndexerFlatHelper->disableProductFlatIndexer();

        $dbAdapter = $this->databaseHelper->getDefaultConnection();

        try {
            $attributeConditions = $this->productFeedHelper->getAttributeConditions($integrations);
            $requiredEavAttributeCodes = $this->productFeedHelper->getAdditionalEavAttributeCodes($integrations);

            if ($this->variables->isEmpty($productIds)) {
                $this->logging->info(
                    sprintf(
                        'Indexing all products for store with id: %d',
                        $storeId
                    )
                );
            } else {
                $this->logging->info(
                    sprintf(
                        'Indexing %d product(s) for store with id: %d',
                        count($productIds),
                        $storeId
                    )
                );
            }

            $showOutOfStock = $this->storeHelper->getStoreConfig(
                'cataloginventory/options/show_out_of_stock',
                false,
                true,
                $storeId
            );
            $limitActiveCategoriesToStore = $this->storeHelper->getStoreConfig(
                'infrangible_catalogproductfeed/export/limit_active_categories_to_store',
                true,
                true,
                $storeId
            );

            $lastProductId = 0;

            while (true) {
                $this->logging->debug(
                    sprintf(
                        'Fetched from database with product id > %d',
                        $lastProductId
                    )
                );

                $chunkProductIds = $this->exportHelper->getExportableProductIds(
                    $storeId,
                    ! $showOutOfStock,
                    $productIds,
                    $lastProductId
                );

                if ($this->variables->isEmpty($chunkProductIds)) {
                    break;
                }

                foreach ($integrations as $integration) {
                    $integration->startBlock(
                        $mode,
                        $storeId,
                        $chunkProductIds
                    );
                }

                $this->logging->debug(
                    sprintf(
                        'Fetched %d product(s) from database',
                        count($chunkProductIds)
                    )
                );

                $productsData = $this->exportHelper->getProductsData(
                    $dbAdapter,
                    $chunkProductIds,
                    $storeId,
                    $attributeConditions,
                    $requiredEavAttributeCodes,
                    $limitActiveCategoriesToStore
                );

                foreach ($productsData as $productData) {
                    $categoryPaths = $this->arrays->getValue(
                        $productData,
                        'category_paths',
                        []
                    );
                    $urlRewrites = $this->arrays->getValue(
                        $productData,
                        'url_rewrites',
                        []
                    );
                    $galleryImages = $this->arrays->getValue(
                        $productData,
                        'gallery_images',
                        []
                    );
                    $indexedPrices = $this->arrays->getValue(
                        $productData,
                        'indexed_prices',
                        []
                    );
                    $stockItem = $this->arrays->getValue(
                        $productData,
                        'stock_item',
                        []
                    );
                    $reviewSummary = $this->arrays->getValue(
                        $productData,
                        'review_summary',
                        []
                    );
                    $children = $this->arrays->getValue(
                        $productData,
                        'children',
                        []
                    );
                    $bundled = $this->arrays->getValue(
                        $productData,
                        'bundled',
                        []
                    );
                    $grouped = $this->arrays->getValue(
                        $productData,
                        'grouped',
                        []
                    );

                    unset($productData[ 'category_paths' ]);
                    unset($productData[ 'url_rewrites' ]);
                    unset($productData[ 'gallery_images' ]);
                    unset($productData[ 'indexed_prices' ]);
                    unset($productData[ 'stock_item' ]);
                    unset($productData[ 'review_summary' ]);
                    unset($productData[ 'children' ]);
                    unset($productData[ 'bundled' ]);
                    unset($productData[ 'grouped' ]);

                    $categoryUrls = [];

                    foreach ($categoryPaths as $categoryPath) {
                        $categoryUrls[] = $this->arrays->getValue(
                            $categoryPath,
                            'url'
                        );
                    }

                    /** @var ExportValidation $exportValidation */
                    $exportValidation = $this->universalFactory->create(ExportValidation::class);

                    $exportValidation->setMode($mode);
                    $exportValidation->setStoreId($storeId);
                    $exportValidation->setProductData($productData);
                    $exportValidation->setCategoryPaths($categoryPaths);
                    $exportValidation->setUrlRewrites($urlRewrites);
                    $exportValidation->setGalleryImages($galleryImages);
                    $exportValidation->setIndexedPrices($indexedPrices);
                    $exportValidation->setStockItem($stockItem);
                    $exportValidation->setReviewSummary($reviewSummary);
                    $exportValidation->setChildren($children);
                    $exportValidation->setBundled($bundled);
                    $exportValidation->setGrouped($grouped);
                    $exportValidation->setResult(true);

                    $this->eventManager->dispatch(
                        'infrangible_catalogproductfeed_export_validation',
                        ['export_validation' => $exportValidation]
                    );

                    if (! $exportValidation->getResult()) {
                        $this->logging->info(
                            sprintf(
                                'Skipping product with sku: %s with name: %s, final price: %s, category urls: %s, in stock: %s in store with id: %d',
                                $this->arrays->getValue(
                                    $productData,
                                    'sku'
                                ),
                                $this->arrays->getValue(
                                    $productData,
                                    'name'
                                ),
                                $this->arrays->getValue(
                                    $indexedPrices,
                                    'final_price'
                                ),
                                implode(
                                    ', ',
                                    $categoryUrls
                                ),
                                var_export(
                                    $this->arrays->getValue(
                                        $stockItem,
                                        'is_in_stock',
                                        0
                                    ) == 1,
                                    true
                                ),
                                $storeId
                            )
                        );

                        continue;
                    }

                    $this->logging->info(
                        sprintf(
                            'Exporting product with sku: %s with name: %s, final price: %s, category urls: %s, in stock: %s in store with id: %d',
                            $this->arrays->getValue(
                                $productData,
                                'sku'
                            ),
                            $this->arrays->getValue(
                                $productData,
                                'name'
                            ),
                            $this->arrays->getValue(
                                $indexedPrices,
                                'final_price'
                            ),
                            implode(
                                ', ',
                                $categoryUrls
                            ),
                            var_export(
                                $this->arrays->getValue(
                                    $stockItem,
                                    'is_in_stock',
                                    0
                                ) == 1,
                                true
                            ),
                            $storeId
                        )
                    );

                    foreach ($integrations as $integration) {
                        $integration->exportProductData(
                            $mode,
                            $storeId,
                            $productData,
                            $categoryPaths,
                            $urlRewrites,
                            $galleryImages,
                            $indexedPrices,
                            $stockItem,
                            $reviewSummary,
                            $children,
                            $bundled,
                            $grouped
                        );

                        foreach ($children as $childProductData) {
                            $childGalleryImages = $this->arrays->getValue(
                                $childProductData,
                                'gallery_images',
                                []
                            );
                            $childIndexedPrices = $this->arrays->getValue(
                                $childProductData,
                                'indexed_prices',
                                []
                            );
                            $childStockItem = $this->arrays->getValue(
                                $childProductData,
                                'stock_item',
                                []
                            );

                            unset($childProductData[ 'category_paths' ]);
                            unset($childProductData[ 'url_rewrites' ]);
                            unset($childProductData[ 'gallery_images' ]);
                            unset($childProductData[ 'indexed_prices' ]);
                            unset($childProductData[ 'stock_item' ]);
                            unset($childProductData[ 'review_summary' ]);
                            unset($childProductData[ 'children' ]);
                            unset($childProductData[ 'bundled' ]);
                            unset($childProductData[ 'grouped' ]);

                            $childProductData[ 'parent' ] = $productData;

                            $this->logging->info(
                                sprintf(
                                    'Exporting child with sku: %s with name: %s, final price: %s, in stock: %s in store with id: %d',
                                    $this->arrays->getValue(
                                        $childProductData,
                                        'sku'
                                    ),
                                    $this->arrays->getValue(
                                        $childProductData,
                                        'name'
                                    ),
                                    $this->arrays->getValue(
                                        $childIndexedPrices,
                                        'final_price'
                                    ),
                                    var_export(
                                        $this->arrays->getValue(
                                            $childStockItem,
                                            'is_in_stock',
                                            0
                                        ) == 1,
                                        true
                                    ),
                                    $storeId
                                )
                            );

                            $integration->exportChildData(
                                $mode,
                                $storeId,
                                $childProductData,
                                $categoryPaths,
                                $urlRewrites,
                                $childGalleryImages,
                                $childIndexedPrices,
                                $childStockItem
                            );
                        }

                        foreach ($bundled as $bundledProductData) {
                            $bundledCategoryPaths = $this->arrays->getValue(
                                $bundledProductData,
                                'category_paths',
                                []
                            );
                            $bundledUrlRewrites = $this->arrays->getValue(
                                $bundledProductData,
                                'url_rewrites',
                                []
                            );
                            $bundledGalleryImages = $this->arrays->getValue(
                                $bundledProductData,
                                'gallery_images',
                                []
                            );
                            $bundledIndexedPrices = $this->arrays->getValue(
                                $bundledProductData,
                                'indexed_prices',
                                []
                            );
                            $bundledStockItem = $this->arrays->getValue(
                                $bundledProductData,
                                'stock_item',
                                []
                            );

                            unset($bundledProductData[ 'category_paths' ]);
                            unset($bundledProductData[ 'url_rewrites' ]);
                            unset($bundledProductData[ 'gallery_images' ]);
                            unset($bundledProductData[ 'indexed_prices' ]);
                            unset($bundledProductData[ 'stock_item' ]);
                            unset($bundledProductData[ 'review_summary' ]);
                            unset($bundledProductData[ 'children' ]);
                            unset($bundledProductData[ 'bundled' ]);
                            unset($bundledProductData[ 'grouped' ]);

                            $bundledProductData[ 'parent' ] = $productData;

                            $this->logging->info(
                                sprintf(
                                    'Exporting bundled with sku: %s with name: %s, final price: %s, in stock: %s in store with id: %d',
                                    $this->arrays->getValue(
                                        $bundledProductData,
                                        'sku'
                                    ),
                                    $this->arrays->getValue(
                                        $bundledProductData,
                                        'name'
                                    ),
                                    $this->arrays->getValue(
                                        $bundledIndexedPrices,
                                        'final_price'
                                    ),
                                    var_export(
                                        $this->arrays->getValue(
                                            $bundledStockItem,
                                            'is_in_stock',
                                            0
                                        ) == 1,
                                        true
                                    ),
                                    $storeId
                                )
                            );

                            $integration->exportBundledData(
                                $mode,
                                $storeId,
                                $bundledProductData,
                                $bundledCategoryPaths,
                                $bundledUrlRewrites,
                                $bundledGalleryImages,
                                $bundledIndexedPrices,
                                $bundledStockItem
                            );
                        }

                        foreach ($grouped as $groupedProductData) {
                            $groupedCategoryPaths = $this->arrays->getValue(
                                $groupedProductData,
                                'category_paths',
                                []
                            );
                            $groupedUrlRewrites = $this->arrays->getValue(
                                $groupedProductData,
                                'url_rewrites',
                                []
                            );
                            $groupedGalleryImages = $this->arrays->getValue(
                                $groupedProductData,
                                'gallery_images',
                                []
                            );
                            $groupedIndexedPrices = $this->arrays->getValue(
                                $groupedProductData,
                                'indexed_prices',
                                []
                            );
                            $groupedStockItem = $this->arrays->getValue(
                                $groupedProductData,
                                'stock_item',
                                []
                            );

                            unset($groupedProductData[ 'category_paths' ]);
                            unset($groupedProductData[ 'url_rewrites' ]);
                            unset($groupedProductData[ 'gallery_images' ]);
                            unset($groupedProductData[ 'indexed_prices' ]);
                            unset($groupedProductData[ 'stock_item' ]);
                            unset($groupedProductData[ 'review_summary' ]);
                            unset($groupedProductData[ 'children' ]);
                            unset($groupedProductData[ 'bundled' ]);
                            unset($groupedProductData[ 'grouped' ]);

                            $groupedProductData[ 'parent' ] = $productData;

                            $this->logging->info(
                                sprintf(
                                    'Exporting grouped with sku: %s with name: %s, final price: %s, in stock: %s in store with id: %d',
                                    $this->arrays->getValue(
                                        $groupedProductData,
                                        'sku'
                                    ),
                                    $this->arrays->getValue(
                                        $groupedProductData,
                                        'name'
                                    ),
                                    $this->arrays->getValue(
                                        $groupedIndexedPrices,
                                        'final_price'
                                    ),
                                    var_export(
                                        $this->arrays->getValue(
                                            $groupedStockItem,
                                            'is_in_stock',
                                            0
                                        ) == 1,
                                        true
                                    ),
                                    $storeId
                                )
                            );

                            $integration->exportGroupedData(
                                $mode,
                                $storeId,
                                $groupedProductData,
                                $groupedCategoryPaths,
                                $groupedUrlRewrites,
                                $groupedGalleryImages,
                                $groupedIndexedPrices,
                                $groupedStockItem
                            );
                        }
                    }
                }

                foreach ($integrations as $integration) {
                    $integration->finishBlock(
                        $mode,
                        $storeId,
                        $chunkProductIds
                    );
                }

                $lastProductId = end($chunkProductIds);
            }

            $this->logging->debug('Finished indexing');
        } catch (Exception $exception) {
            $this->logging->error(
                sprintf(
                    'Could not rebuild store with id: %d because: %s',
                    $storeId,
                    $exception->getMessage()
                )
            );
            $this->logging->error($exception);
        }

        $this->catalogIndexerFlatHelper->removeDisableProductFlatIndexer();

        foreach ($integrations as $integration) {
            $integration->finishStore(
                $mode,
                $storeId,
                $productIds
            );
        }
    }

    protected function dismantle(bool $success): void
    {
    }

    public function isEmptyRun(): bool
    {
        return $this->isEmptyRun;
    }

    public function setIsEmptyRun(bool $isEmptyRun): void
    {
        $this->isEmptyRun = $isEmptyRun;
    }
}
