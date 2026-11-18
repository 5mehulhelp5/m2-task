<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Cron;

use Infrangible\Task\Model\ResourceModel\Run\CollectionFactory;
use Infrangible\Task\Model\ResourceModel\RunFactory;
use Infrangible\Task\Model\Run;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;

/**
 * Finishes runs whose process no longer exists, for example after the process was killed. The list of runs shows them
 * as broken: they have no memory usage.
 *
 * Only processes of the server that runs the cron job can be checked; on servers without /proc, runs stay unchanged.
 */
class Check
{
    /**
     * @param CollectionFactory $runCollectionFactory
     * @param RunFactory $runResourceFactory
     * @param File $file
     */
    public function __construct(
        protected CollectionFactory $runCollectionFactory,
        protected RunFactory $runResourceFactory,
        protected File $file
    ) {
    }

    /**
     * Finishes the runs without process.
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws FileSystemException
     * @throws \Exception
     */
    public function execute(): void
    {
        if (! $this->file->isDirectory('/proc/self')) {
            return;
        }

        $collection = $this->runCollectionFactory->create();
        $collection->addIsRunningFilter();

        $runResource = $this->runResourceFactory->create();

        /** @var Run $run */
        foreach ($collection as $run) {
            $processId = $run->getData('process_id');

            if (is_numeric($processId) && (int)$processId > 0 &&
                ! $this->file->isDirectory('/proc/' . (int)$processId)) {
                $run->setData('success', 0);
                $run->setData('finish_at', gmdate('Y-m-d H:i:s'));
                $runResource->save($run);
            }
        }
    }
}
