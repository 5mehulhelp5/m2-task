<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Logger;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;

/**
 * The log files of the runs: var/log/task/<task name>/<store code>/<task ID>.log for messages and .err for errors.
 */
class LogFiles
{
    /** Extension of the log file with the messages. */
    public const LOG = 'log';

    /** Extension of the log file with the errors. */
    public const ERROR = 'err';

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(protected DirectoryList $directoryList)
    {
    }

    /**
     * Returns the path of a log file of a run.
     *
     * Characters that are not allowed in file names, for example slashes in a task ID given on the console, are
     * replaced, so that the file stays in the log directory.
     *
     * @param string $taskName
     * @param string $storeCode
     * @param string $taskId
     * @param string $extension
     *
     * @return string
     * @throws FileSystemException
     */
    public function getPath(string $taskName, string $storeCode, string $taskId, string $extension): string
    {
        return implode(
            '/',
            [
                $this->directoryList->getPath(DirectoryList::LOG),
                'task',
                $this->sanitize($taskName),
                $this->sanitize($storeCode),
                $this->sanitize($taskId) . '.' . $extension
            ]
        );
    }

    /**
     * Replaces characters that are not allowed in a part of a path.
     *
     * @param string $part
     *
     * @return string
     */
    private function sanitize(string $part): string
    {
        $part = (string)preg_replace('/[^A-Za-z0-9_.-]/', '_', $part);

        return trim($part, '.') === '' ? '_' : $part;
    }
}
