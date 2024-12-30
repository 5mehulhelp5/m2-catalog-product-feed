<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Task\Delta\Cron;

use Infrangible\Task\Cron\Execution\Base;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Execution extends Base
{
    protected function getTaskName(): string
    {
        return 'catalog_product_feed_delta';
    }
}
