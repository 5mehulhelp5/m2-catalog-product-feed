<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class FinalPriceCurrency extends AbstractCustom
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
        $typeId = $this->arrays->getValue(
            $productData,
            'type_id'
        );

        if ($typeId === 'grouped' || $typeId === 'bundle') {
            $price = $this->arrays->getValue(
                $indexedPrices,
                'min_price'
            );
        } else {
            if (array_key_exists(
                'final_price',
                $indexedPrices
            )) {
                $price = $indexedPrices[ 'final_price' ];
            } else {
                $price = $this->arrays->getValue(
                    $productData,
                    'price'
                );
            }
        }

        return $this->variables->isEmpty($price) ? null : $this->customHelper->formatPrice(
            $storeId,
            $price,
            true
        );
    }

    /**
     * @return string[]
     */
    public function requireEavAttributeCodes(): array
    {
        return ['price'];
    }
}
