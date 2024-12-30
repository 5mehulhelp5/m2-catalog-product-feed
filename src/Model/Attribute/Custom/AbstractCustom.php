<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\Custom;
use Infrangible\CatalogProductFeed\Helper\Data;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Stores;
use Infrangible\Core\Helper\Url;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class AbstractCustom
{
    /** @var Variables */
    protected $variables;

    /** @var Arrays */
    protected $arrays;

    /** @var Database */
    protected $databaseHelper;

    /** @var Url */
    protected $urlHelper;

    /** @var Stores */
    protected $storeHelper;

    /** @var Data */
    protected $productFeedHelper;

    /** @var Custom */
    protected $customHelper;

    /** @var LoggerInterface */
    protected $logging;

    public function __construct(
        Variables $variables,
        Arrays $arrayHelper,
        Database $databaseHelper,
        Url $urlHelper,
        Stores $storeHelper,
        Data $productFeedHelper,
        Custom $customHelper,
        LoggerInterface $logging
    ) {
        $this->variables = $variables;
        $this->arrays = $arrayHelper;
        $this->databaseHelper = $databaseHelper;
        $this->urlHelper = $urlHelper;
        $this->storeHelper = $storeHelper;
        $this->productFeedHelper = $productFeedHelper;
        $this->customHelper = $customHelper;
        $this->logging = $logging;
    }

    abstract public function process(
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
    );

    /**
     * @return string[]
     */
    abstract public function requireEavAttributeCodes(): array;
}
