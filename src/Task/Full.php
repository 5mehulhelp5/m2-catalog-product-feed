<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Task;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Files;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\ChangeLog;
use Infrangible\CatalogProductFeed\Helper\Data;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\CollectionFactory;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Export;
use Infrangible\Core\Helper\Registry;
use Infrangible\Core\Helper\Stores;
use Infrangible\SimpleMail\Model\MailFactory;
use Infrangible\Task\Model\RunFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Validator\UniversalFactory;
use Psr\Log\LoggerInterface;
use Zend_Db_Select_Exception;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Full extends Base
{
    /** @var ChangeLog */
    protected $productFeedChangeLogHelper;

    /** @var string */
    private $taskRunTimeFile;

    /** @var int */
    private $timeStamp;

    public function __construct(
        Files $files,
        Registry $registryHelper,
        \Infrangible\Task\Helper\Data $helper,
        LoggerInterface $logging,
        DirectoryList $directoryList,
        RunFactory $runFactory,
        \Infrangible\Task\Model\ResourceModel\RunFactory $runResourceFactory,
        MailFactory $mailFactory,
        Variables $variables,
        Stores $storeHelper,
        Database $databaseHelper,
        Arrays $arrayHelper,
        Export $catalogExportHelper,
        Data $productFeedHelper,
        CollectionFactory $attributeCollectionFactory,
        \Infrangible\CatalogIndexerFlat\Helper\Data $catalogIndexerFlatHelper,
        UniversalFactory $universalFactory,
        ManagerInterface $eventManager,
        ChangeLog $productFeedChangeLogHelper
    ) {
        parent::__construct(
            $files,
            $registryHelper,
            $helper,
            $logging,
            $directoryList,
            $runFactory,
            $runResourceFactory,
            $mailFactory,
            $variables,
            $storeHelper,
            $databaseHelper,
            $arrayHelper,
            $catalogExportHelper,
            $productFeedHelper,
            $attributeCollectionFactory,
            $catalogIndexerFlatHelper,
            $universalFactory,
            $eventManager
        );

        $this->productFeedChangeLogHelper = $productFeedChangeLogHelper;
    }

    /**
     * @throws FileSystemException
     */
    protected function prepare(): void
    {
        parent::prepare();

        $this->taskRunTimeFile = sprintf(
            '%s/task/%s.run',
            $this->directoryList->getPath(DirectoryList::VAR_DIR),
            $this->getTaskName()
        );

        $this->files->createDirectory(dirname($this->taskRunTimeFile));
    }

    /**
     * @throws Exception
     */
    protected function runTask(): bool
    {
        $this->logging->info('Starting product feed full export');

        if ($this->isForce()) {
            $lastRunTime = null;
        } else {
            if (file_exists($this->taskRunTimeFile)) {
                $lastRunTime = (int)file_get_contents($this->taskRunTimeFile);
            } else {
                $lastRunTime = time();
            }
        }

        $this->reindexAll(
            Data::MODE_FULL,
            $this->getStoreCode(),
            $this->getIntegrationNames(),
            $lastRunTime
        );

        $this->logging->info('Finished product feed full export');

        return true;
    }

    /**
     * @throws Zend_Db_Select_Exception
     * @throws Exception
     */
    public function reindexAll(
        string $useMode,
        ?string $storeCode = null,
        ?array $integrationNames = [],
        ?int $lastRunTime = null
    ) {
        $this->timeStamp = time();

        $this->reindexProducts(
            $useMode,
            $storeCode,
            [],
            $integrationNames,
            $lastRunTime
        );

        $this->productFeedChangeLogHelper->removeEntityIds(
            gmdate(
                'Y-m-d H:i:s',
                $this->timeStamp
            )
        );
    }

    protected function dismantle(bool $success): void
    {
        parent::dismantle($success);

        file_put_contents(
            $this->taskRunTimeFile,
            $this->timeStamp
        );
    }
}
