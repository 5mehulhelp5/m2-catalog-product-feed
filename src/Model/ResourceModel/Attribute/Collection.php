<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute;

use Infrangible\CatalogProductFeed\Model\Attribute;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            Attribute::class,
            \Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute::class
        );
    }

    public function filterByIntegration(string $integrationName): void
    {
        $this->getSelect()->where(
            'integration = ?',
            $integrationName
        );
    }

    public function filterDeltaAttributes(): void
    {
        $this->getSelect()->where(
            'use_for_delta = ?',
            1
        );
    }
}
