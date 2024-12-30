<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model;

use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\Collection;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
interface IIntegration
{
    public function start(string $mode, ?int $lastRunTime = null): void;

    public function finish(string $mode): void;

    public function startStore(
        string $mode,
        int $storeId,
        array $productIds,
        Collection $attributeCollection
    ): void;

    public function finishStore(string $mode, int $storeId, array $productIds): void;

    public function startBlock(string $mode, int $storeId, array $productIds): void;

    public function finishBlock(string $mode, int $storeId, array $productIds): void;

    public function exportProductData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem,
        array $reviewSummary,
        array $children,
        array $bundled,
        array $grouped
    ): void;

    public function exportChildData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem
    ): void;

    public function exportBundledData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem
    ): void;

    public function exportGroupedData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem
    ): void;

    public function isEnabled(int $storeId): bool;

    public function useForDelta(int $storeId): bool;

    /**
     * @return string[]
     */
    public function getRequiredEavAttributeCodes(): array;

    /**
     * @return string[]
     */
    public function getAttributeConditions(): array;

    public function isScheduled(int $storeId): bool;

    /**
     * @return string[]
     */
    public function getScheduleExpression(int $storeId): array;
}
