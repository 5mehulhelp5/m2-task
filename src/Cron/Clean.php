<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Cron;

use Infrangible\Task\Logger\LogFiles;
use Infrangible\Task\Model\ResourceModel\Run\CollectionFactory;
use Infrangible\Task\Model\ResourceModel\RunFactory;
use Infrangible\Task\Model\Run;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;

/**
 * Deletes the runs that started more than 90 days ago, with their log files.
 */
class Clean
{
    /**
     * @param CollectionFactory $runCollectionFactory
     * @param RunFactory $runResourceFactory
     * @param LogFiles $logFiles
     * @param File $file
     */
    public function __construct(
        protected CollectionFactory $runCollectionFactory,
        protected RunFactory $runResourceFactory,
        protected LogFiles $logFiles,
        protected File $file
    ) {
    }

    /**
     * Deletes the old runs and their log files.
     *
     * @return void
     * @throws FileSystemException
     * @throws \Exception
     */
    public function execute(): void
    {
        $collection = $this->runCollectionFactory->create();
        $collection->addStartAtFilter();

        $runResource = $this->runResourceFactory->create();

        /** @var Run $run */
        foreach ($collection as $run) {
            foreach ([LogFiles::LOG, LogFiles::ERROR] as $extension) {
                $path = $this->logFiles->getPath(
                    $this->stringValue($run->getData('task_name')),
                    $this->stringValue($run->getData('store_code')),
                    $this->stringValue($run->getData('task_id')),
                    $extension
                );

                if ($this->file->isFile($path)) {
                    $this->file->deleteFile($path);
                }
            }

            $runResource->delete($run);
        }
    }

    /**
     * Returns a value of the run as string.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
