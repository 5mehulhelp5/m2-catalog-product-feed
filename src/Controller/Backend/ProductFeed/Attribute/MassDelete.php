<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Controller\Backend\ProductFeed\Attribute;

use Infrangible\CatalogProductFeed\Traits\Attribute;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class MassDelete extends \Infrangible\BackendWidget\Controller\Backend\Object\MassDelete
{
    use Attribute;

    protected function getObjectsDeletedMessage(): string
    {
        return __('%d attributes have been deleted.')->render();
    }
}
