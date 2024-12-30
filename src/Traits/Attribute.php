<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Traits;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
trait Attribute
{
    protected function getObjectName(): string
    {
        return 'Attribute';
    }

    protected function getObjectField(): ?string
    {
        return 'attribute_id';
    }

    protected function allowAdd(): bool
    {
        return true;
    }

    protected function allowEdit(): bool
    {
        return true;
    }

    protected function allowView(): bool
    {
        return false;
    }

    protected function allowDelete(): bool
    {
        return true;
    }

    protected function getObjectNotFoundMessage(): string
    {
        return __('The attribute with id: %s does not exist.')->render();
    }
}
