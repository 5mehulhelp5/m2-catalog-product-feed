<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Task\Delta;

use Infrangible\CatalogProductFeed\Task\Delta;
use Infrangible\Task\Cron\Base;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Cron extends Base
{
    protected function getTaskName(): string
    {
        return 'catalog_product_feed_delta';
    }

    protected function getClassName(): string
    {
        return Delta::class;
    }
}
