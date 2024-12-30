<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Controller\Backend\ProductFeed\Attribute;

use Infrangible\CatalogProductFeed\Traits\Attribute;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Save extends \Infrangible\BackendWidget\Controller\Backend\Object\Save
{
    use Attribute;

    protected function getObjectCreatedMessage(): string
    {
        return __('The attribute has been created.')->render();
    }

    protected function getObjectUpdatedMessage(): string
    {
        return __('The attribute has been updated.')->render();
    }
}
