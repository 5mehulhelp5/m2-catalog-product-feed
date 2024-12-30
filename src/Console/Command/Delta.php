<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Console\Command;

use Infrangible\Task\Console\Command\Task;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Delta extends Task
{
    protected function getTaskName(): string
    {
        return 'catalog_product_feed_delta';
    }

    protected function getCommandDescription(): string
    {
        return 'Process all product feed changes';
    }

    protected function getClassName(): string
    {
        return Script\Delta::class;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        parent::configure();

        $definition = $this->getDefinition();

        $definition->addOption(
            new InputOption(
                'integration',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name of integrations to create feed'
            )
        );
        $definition->addOption(
            new InputOption(
                'store_id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Id of the store to create feed'
            )
        );
        $definition->addArgument(
            new InputArgument(
                'force',
                InputArgument::OPTIONAL,
                'Ignore schedules'
            )
        );
    }
}
