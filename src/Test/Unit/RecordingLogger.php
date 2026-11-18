<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Test\Unit;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * A logger for the tests that collects the messages by level.
 */
class RecordingLogger extends AbstractLogger
{
    /**
     * @var array
     * @phpstan-var array<int, array{0: string, 1: string}>
     */
    private array $records = [];

    /**
     * Collects the record.
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
        $this->records[] = [is_string($level) ? $level : '', (string)$message];
    }

    /**
     * Returns the messages of a level.
     *
     * @param string $level
     *
     * @return string[]
     */
    public function getMessages(string $level): array
    {
        $messages = [];

        foreach ($this->records as [$recordLevel, $message]) {
            if ($recordLevel === $level) {
                $messages[] = $message;
            }
        }

        return $messages;
    }
}
