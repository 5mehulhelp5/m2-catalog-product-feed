<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Plugin\Catalog\Model;

use Exception;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\ChangeLog;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Product
{
    /** @var ChangeLog */
    protected $changeLogHelper;

    /** @var Variables */
    protected $variables;

    public function __construct(ChangeLog $changeLogHelper, Variables $variables)
    {
        $this->changeLogHelper = $changeLogHelper;
        $this->variables = $variables;
    }

    /**
     * @throws Exception
     */
    public function afterPriceReindexCallback(\Magento\Catalog\Model\Product $product): void
    {
        if ($this->changeLogHelper->isPriceUpdateRequired($product)) {
            $productIds = $this->changeLogHelper->collectProductIds($this->variables->intValue($product->getId()));

            $this->changeLogHelper->insertEntityIds(
                $productIds,
                \Magento\Catalog\Model\Product::ENTITY
            );
        }
    }

    /**
     * @throws Exception
     */
    public function afterReindex(\Magento\Catalog\Model\Product $product): void
    {
        if ($this->changeLogHelper->isPriceUpdateRequired($product)) {
            return;
        }

        if ($this->changeLogHelper->isAttributeUpdateRequired($product) ||
            $this->changeLogHelper->isStockUpdateRequired($product)) {

            $productIds = $this->changeLogHelper->collectProductIds($this->variables->intValue($product->getId()));

            $this->changeLogHelper->insertEntityIds(
                $productIds,
                \Magento\Catalog\Model\Product::ENTITY
            );
        }
    }
}
