<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Logger\Summary;

use Infrangible\Task\Logger\Summary;

/**
 * Summary of a task: The errors (and warnings, if they are logged as errors), whatever log level is configured.
 */
class Error extends Summary
{
    /**
     * Returns the type.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'error';
    }

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
}
