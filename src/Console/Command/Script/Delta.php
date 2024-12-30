<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Console\Command\Script;

use Infrangible\Task\Console\Command\Script\Task;
use Infrangible\Task\Task\Base;
use Symfony\Component\Console\Input\InputInterface;

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

    protected function getClassName(): string
    {
        return \Infrangible\CatalogProductFeed\Task\Delta::class;
    }

    protected function prepareTask(Base $task, InputInterface $input): void
    {
        parent::prepareTask(
            $task,
            $input
        );

        if ($task instanceof \Infrangible\CatalogProductFeed\Task\Delta) {
            $integration = $input->getOption('integration');

            if ($integration) {
                $integrationNames = explode(
                    ',',
                    $integration
                );

                $task->setIntegrationNames(
                    array_map(
                        'trim',
                        $integrationNames
                    )
                );
            }

            if ($input->getOption('force')) {
                $task->setForce(true);
            }
        }
    }
}
