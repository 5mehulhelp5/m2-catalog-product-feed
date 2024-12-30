<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ProductUrl extends AbstractCustom
{
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
    ): string {
        foreach ($urlRewrites as $categoryId => $url) {
            if ($categoryId === 0) {
                return $this->urlHelper->getUrl(
                    '',
                    null,
                    ['_direct' => $url],
                    $storeId
                );
            }
        }

        return $this->urlHelper->getUrl(
            '',
            null,
            [
                '_direct' => $this->arrays->getValue(
                    $productData,
                    'url_path'
                )
            ],
            $storeId
        );
    }

    /**
     * @return string[]
     */
    public function requireEavAttributeCodes(): array
    {
        return ['url_path'];
    }
}
