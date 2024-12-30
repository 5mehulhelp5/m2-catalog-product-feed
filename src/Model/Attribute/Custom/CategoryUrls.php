<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CategoryUrls extends AbstractCustom
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
    ): array {
        $result = [];

        foreach ($categoryPaths as $categoryId => $categoryData) {
            $result[ $categoryId ] = $this->urlHelper->getUrl(
                '',
                null,
                [
                    '_direct' => $this->arrays->getValue(
                        $categoryData,
                        'url'
                    )
                ],
                $storeId
            );
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
