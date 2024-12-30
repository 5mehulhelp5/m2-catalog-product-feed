<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Helper;

use Infrangible\CatalogProductFeed\Model\Attribute\Custom\AbstractCustom;
use Infrangible\Core\Helper\Product;
use Infrangible\Core\Helper\Stores;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Custom
{
    /** @var Stores */
    protected $storeHelper;

    /** @var Product */
    protected $productHelper;

    /** @var ObjectManagerInterface */
    protected $objectManager;

    /** @var \Infrangible\CatalogProductFeed\Model\Config\Custom */
    protected $config;

    public function __construct(
        Stores $storeHelper,
        Product $objectHelper,
        ObjectManagerInterface $objectManager,
        \Infrangible\CatalogProductFeed\Model\Config\Custom $config
    ) {
        $this->storeHelper = $storeHelper;
        $this->productHelper = $objectHelper;
        $this->objectManager = $objectManager;
        $this->config = $config;
    }

    public function getCustomFieldData(): array
    {
        return $this->config->get();
    }

    public function getCustomFieldModel(string $customFieldName): ?AbstractCustom
    {
        $customFieldList = $this->getCustomFieldData();

        $customFieldData = array_key_exists(
            $customFieldName,
            $customFieldList
        ) ? $customFieldList[ $customFieldName ] : [];

        if (! array_key_exists(
            'model',
            $customFieldData
        )) {
            return null;
        }

        /** @var AbstractCustom $customFieldModel */
        $customFieldModel = $this->objectManager->get($customFieldData[ 'model' ]);

        return $customFieldModel;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getImageUrl(int $storeId, string $imagePath, ?string $size = null): ?string
    {
        $url = null;

        $store = $this->storeHelper->getStore($storeId);

        $productMediaConfig = $this->productHelper->getProductMediaConfig();

        $cachedFile = $productMediaConfig->getBaseMediaPath() . '/' . $size . $imagePath;

        if (! is_null($size) && file_exists($cachedFile)) {
            # re-sized image is cached
            $url = $store->getBaseUrl('media') . 'catalog/product/' . $size . $imagePath;
        } elseif (! is_null($size)) {
            # re-sized image is not cached
            $url = $store->getBaseUrl() . 'catalog/product/image/size/' . $size . $imagePath;
        } elseif ($imagePath) {
            # using original image
            $url = $store->getBaseUrl('media') . 'catalog/product' . $imagePath;
        }

        return $url;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function formatPrice(int $storeId, float $price, bool $withCurrency = false): string
    {
        return number_format(
                $price,
                2,
                '.',
                ''
            ) . ($withCurrency ? (' ' . $this->storeHelper->getStore($storeId)->getCurrentCurrencyCode()) : '');
    }
}
