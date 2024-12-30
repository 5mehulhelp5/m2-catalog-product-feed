<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Integration;

use Exception;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\Collection;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Simple extends Base
{
    /** @var int */
    private $lastRunTime;

    public function start(string $mode, ?int $lastRunTime = null): void
    {
        $this->setLastRunTime($lastRunTime);
    }

    protected function getLastRunTime(): ?int
    {
        return $this->lastRunTime;
    }

    protected function setLastRunTime(?int $lastRunTime): void
    {
        $this->lastRunTime = $lastRunTime;
    }

    /**
     * @throws Exception
     */
    public function startStore(
        string $mode,
        int $storeId,
        array $productIds,
        Collection $attributeCollection
    ): void {
        $this->setupAttributes($attributeCollection);
    }

    public function startBlock(string $mode, int $storeId, array $productIds): void
    {
    }

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
    ): void {
        $exportData = $this->getExportData(
            $mode,
            $this->getProductAttributeCodes(),
            $storeId,
            $productData,
            false,
            $categoryPaths,
            $urlRewrites,
            $galleryImages,
            $indexedPrices,
            $stockItem,
            $reviewSummary,
            $children,
            $bundled,
            $grouped
        );

        if (! $this->variables->isEmpty($exportData)) {
            $this->exportProduct(
                $mode,
                $storeId,
                $exportData
            );
        }
    }

    abstract protected function exportProduct(string $mode, int $storeId, array $exportData): void;

    public function exportChildData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem
    ): void {
        $exportData = $this->getExportData(
            $mode,
            $this->getVariantAttributeCodes(),
            $storeId,
            $productData,
            true,
            $categoryPaths,
            $urlRewrites,
            $galleryImages,
            $indexedPrices,
            $stockItem,
            [],
            [],
            [],
            []
        );

        if (! $this->variables->isEmpty($exportData)) {
            $this->exportChild(
                $mode,
                $storeId,
                $exportData
            );
        }
    }

    abstract protected function exportChild(string $mode, int $storeId, array $exportData): void;

    public function exportBundledData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem
    ): void {
        $exportData = $this->getExportData(
            $mode,
            $this->getVariantAttributeCodes(),
            $storeId,
            $productData,
            true,
            $categoryPaths,
            $urlRewrites,
            $galleryImages,
            $indexedPrices,
            $stockItem,
            [],
            [],
            [],
            []
        );

        if (! $this->variables->isEmpty($exportData)) {
            $this->exportBundled(
                $mode,
                $storeId,
                $exportData
            );
        }
    }

    abstract protected function exportBundled(string $mode, int $storeId, array $exportData): void;

    public function exportGroupedData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem
    ): void {
        $exportData = $this->getExportData(
            $mode,
            $this->getVariantAttributeCodes(),
            $storeId,
            $productData,
            true,
            $categoryPaths,
            $urlRewrites,
            $galleryImages,
            $indexedPrices,
            $stockItem,
            [],
            [],
            [],
            []
        );

        if (! $this->variables->isEmpty($exportData)) {
            $this->exportGrouped(
                $mode,
                $storeId,
                $exportData
            );
        }
    }

    abstract protected function exportGrouped(string $mode, int $storeId, array $exportData): void;

    public function finishBlock(string $mode, int $storeId, array $productIds): void
    {
    }

    public function finishStore(string $mode, int $storeId, array $productIds): void
    {
    }

    public function finish(string $mode): void
    {
    }
}
