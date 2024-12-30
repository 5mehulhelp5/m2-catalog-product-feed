<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class SpecialPrice extends AbstractCustom
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
            'special_price'
        );

        return $this->variables->isEmpty($price) ? null : $this->customHelper->formatPrice(
            $storeId,
            $price
        );
    }

    /**
     * @return string[]
     */
    public function requireEavAttributeCodes(): array
    {
        return ['special_price'];
    }
}
