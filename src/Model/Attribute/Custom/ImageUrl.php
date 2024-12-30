<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ImageUrl extends AbstractCustom
{
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
    ): ?string {
        return array_key_exists(
            'image',
            $productData
        ) ? $this->customHelper->getImageUrl(
            $storeId,
            $this->arrays->getValue(
                $productData,
                'image'
            )
        ) : null;
    }

    /**
     * @return string[]
     */
    public function requireEavAttributeCodes(): array
    {
        return ['image'];
    }
}
