<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Console\Command;

use Infrangible\Task\Console\Command\Task;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Full extends Task
{
    protected function getTaskName(): string
    {
        return 'catalog_product_feed_full';
    }

    protected function getClassName(): string
    {
        return Script\Full::class;
    }

    protected function getCommandDescription(): string
    {
        return 'Process all product feeds';
    }

    protected function getCommandDefinition(): array
    {
        $commandDefinition = parent::getCommandDefinition();

        $commandDefinition[] = new InputOption(
            'integration',
            null,
            InputOption::VALUE_OPTIONAL,
            'Name of integrations to create feed'
        );

        $commandDefinition[] = new InputOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Ignore schedules'
        );

        return $commandDefinition;
    }
}
