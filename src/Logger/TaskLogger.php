<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Logger;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Base of the loggers of tasks, which m2-log adds to the logger of Magento: they only log while a task runs, and each
 * logger decides by the severity which records it takes.
 */
abstract class TaskLogger extends AbstractLogger
{
    /**
     * @param TaskContext $taskContext
     */
    public function __construct(protected TaskContext $taskContext)
    {
    }

    /**
     * Logs the record, if a task runs and the logger takes records of this level.
     *
     * @param mixed $level
     * @param string|Stringable $message
     * @param array $context
     * @phpstan-param array<int|string, mixed> $context
     *
     * @return void
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $logLevels = $this->taskContext->getLogLevels();

        if ($this->taskContext->isActive() && $this->isHandling($logLevels->getSeverity($level))) {
            $this->write($logLevels->getName($level), $this->interpolate((string)$message, $context));
        }
    }

    /**
     * Checks whether the logger takes records of this severity.
     *
     * @param int $severity
     *
     * @return bool
     */
    abstract protected function isHandling(int $severity): bool;

    /**
     * Writes the record.
     *
     * @param string $level
     * @param string $message
     *
     * @return void
     */
    abstract protected function write(string $level, string $message): void;

    /**
     * Returns a line with time, level and message.
     *
     * @param string $level
     * @param string $message
     *
     * @return string
     */
    protected function formatLine(string $level, string $message): string
    {
        return sprintf('[%s] %s: %s', date('Y-m-d H:i:s'), strtoupper($level), $message);
    }

    /**
     * Replaces the placeholders of PSR-3 ({key}) with the values of the context.
     *
     * @param string $message
     * @param array $context
     * @phpstan-param array<int|string, mixed> $context
     *
     * @return string
     */
    private function interpolate(string $message, array $context): string
    {
        $replacements = [];

        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value instanceof Stringable) {
                $replacements[ '{' . $key . '}' ] = (string)$value;
            }
        }

        return strtr($message, $replacements);
    }
}
