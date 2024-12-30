<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Indexer;

use Magento\Framework\Model\AbstractModel;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 *
 * @method string getChangelogId()
 * @method void setChangelogId(string $changelogId)
 * @method string getEntityTypeId()
 * @method void setEntityTypeId(string $entityTypeId)
 * @method string getEntityId()
 * @method void setEntityId(string $entityId)
 * @method string getCreatedAt()
 * @method void setCreatedAt(string $createdAt)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt(string $updatedAt)
 */
class Changelog extends AbstractModel
{
    protected $_eventPrefix = 'infrangible_catalogproductfeed_indexer_changelog';

    protected function _construct(): void
    {
        $this->_init(\Infrangible\CatalogProductFeed\Model\ResourceModel\Indexer\Changelog::class);
    }

    public function beforeSave(): Changelog
    {
        if ($this->isObjectNew()) {
            $this->setCreatedAt(gmdate('Y-m-d H:i:s'));
        }

        $this->setUpdatedAt(gmdate('Y-m-d H:i:s'));

        return parent::beforeSave();
    }
}
