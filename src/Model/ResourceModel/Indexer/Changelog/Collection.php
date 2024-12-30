<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\ResourceModel\Indexer\Changelog;

use Infrangible\CatalogProductFeed\Model\Indexer\Changelog;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(
            Changelog::class,
            \Infrangible\CatalogProductFeed\Model\ResourceModel\Indexer\Changelog::class
        );
    }

    public function addEntityTypeIdFilter(int $entityTypeId): void
    {
        $this->addFieldToFilter(
            'entity_type_id',
            $entityTypeId
        );
    }
}
