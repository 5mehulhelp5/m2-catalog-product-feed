<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Price extends AbstractCustom
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
        $price = $this->arrays->getValue(
            $productData,
            'price'
        );

        return array_key_exists(
            'price',
            $productData
        ) ? $this->customHelper->formatPrice(
            $storeId,
            $price
        ) : null;
    }

    /**
     * @return string[]
     */
    public function requireEavAttributeCodes(): array
    {
        return ['price'];
    }
}
