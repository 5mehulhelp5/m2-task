<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Logger;

/**
 * Log file of a run with the errors (and warnings, if they are logged as errors), whatever log level is configured.
 */
class ErrorLog extends FileLog
{
    /**
     * Checks whether it is handling.
     *
     * @param int $severity
     *
     * @return bool
     */
    protected function isHandling(int $severity): bool
    {
        return $severity >= $this->taskContext->getErrorSeverity();
    }

    /**
     * Returns the file extension.
     *
     * @return string
     */
    protected function getFileExtension(): string
    {
        return LogFiles::ERROR;
    }
}
