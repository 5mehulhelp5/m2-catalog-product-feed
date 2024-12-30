<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ProductCategoryUrls extends AbstractCustom
{
    /** @var string */
    protected $productCategoryRewritesSelect;

    /**
     * @throws NoSuchEntityException
     */
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
    ): array {
        $result = [];

        foreach ($urlRewrites as $idPath => $url) {
            if (preg_match(
                '#product/[0-9]+/([0-9]+)#',
                $idPath,
                $matches
            )) {
                if (array_key_exists(
                    1,
                    $matches
                )) {
                    $result[ $matches[ 1 ] ] = $this->urlHelper->getUrl(
                        '',
                        null,
                        ['_direct' => $url]
                    );
                }
            } else {
                $result[ $this->storeHelper->getStore($storeId)->getRootCategoryId() ] = $this->urlHelper->getUrl(
                    '',
                    null,
                    ['_direct' => $url]
                );
            }
        }

        return $result;
    }

    /**
     * @return string[]
     */
    public function requireEavAttributeCodes(): array
    {
        return [];
    }
}
