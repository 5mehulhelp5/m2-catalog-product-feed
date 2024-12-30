<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\Custom;
use Infrangible\CatalogProductFeed\Helper\Data;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Product;
use Infrangible\Core\Helper\Seo;
use Infrangible\Core\Helper\Stores;
use Infrangible\Core\Helper\Url;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ProductUrlWithKey extends AbstractCustom
{
    /** @var Product */
    protected $productHelper;

    /** @var Seo */
    protected $catalogSeoHelper;

    public function __construct(
        Variables $variables,
        Arrays $arrayHelper,
        Database $databaseHelper,
        Url $urlHelper,
        Stores $storeHelper,
        Data $productFeedHelper,
        Custom $customHelper,
        LoggerInterface $logging,
        Product $productHelper,
        Seo $catalogSeoHelper
    ) {
        parent::__construct(
            $variables,
            $arrayHelper,
            $databaseHelper,
            $urlHelper,
            $storeHelper,
            $productFeedHelper,
            $customHelper,
            $logging
        );

        $this->productHelper = $productHelper;
        $this->catalogSeoHelper = $catalogSeoHelper;
    }

    public function process(
        int $storeId,
        array $productData,
        bool $isChild,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem,
        array $children,
        array $bundled,
        array $grouped
    ): ?string {
        if (array_key_exists(
            'url_key',
            $productData
        )) {
            $urlKey = $productData[ 'url_key' ];

            $product = $this->productHelper->newProduct();

            return $this->urlHelper->getUrl(
                '',
                null,
                ['_direct' => $this->catalogSeoHelper->addSeoSuffix($product->formatUrlKey($urlKey))],
                $storeId
            );
        }

        return null;
    }

    public function requireEavAttributeCodes(): array
    {
        return ['url_key'];
    }
}
