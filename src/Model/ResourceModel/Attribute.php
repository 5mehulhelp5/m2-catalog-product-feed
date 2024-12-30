<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Attribute extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(
            'catalog_product_feed_attribute',
            'attribute_id'
        );
    }
}
