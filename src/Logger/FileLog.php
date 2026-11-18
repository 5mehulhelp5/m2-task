<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Logger;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;

/**
 * Writes the records of a task into a log file of the run.
 */
abstract class FileLog extends TaskLogger
{
    /**
     * @param TaskContext $taskContext
     * @param LogFiles $logFiles
     * @param File $file
     */
    public function __construct(TaskContext $taskContext, protected LogFiles $logFiles, protected File $file)
    {
        parent::__construct($taskContext);
    }

    /**
     * Appends the record to the log file. A log file that cannot be written does not stop the task.
     *
     * @param string $level
     * @param string $message
     *
     * @return void
     */
    protected function write(string $level, string $message): void
    {
        try {
            $path = $this->logFiles->getPath(
                $this->taskContext->getTaskName(),
                $this->taskContext->getStoreCode(),
                $this->taskContext->getTaskId(),
                $this->getFileExtension()
            );

            $this->file->createDirectory($this->file->getParentDirectory($path));

            $resource = $this->file->fileOpen($path, 'a');

            $this->file->fileWrite($resource, $this->formatLine($level, $message) . PHP_EOL);
            $this->file->fileClose($resource);
        } catch (FileSystemException|NoSuchEntityException) {
            // the logger of Magento has no better place to report it either
            return;
        }
    }

    /**
     * Returns the extension of the log file.
     *
     * @return string
     */
    abstract protected function getFileExtension(): string;
}
