<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Logger;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shows the records of a task on the console, if the task was started on the console with the option --console.
 */
class ConsoleLog extends TaskLogger
{
    /** @var OutputInterface|null */
    private ?OutputInterface $output = null;

    /**
     * Checks whether it is handling.
     *
     * @param int $severity
     *
     * @return bool
     */
    protected function isHandling(int $severity): bool
    {
        return $this->taskContext->isConsole() && $severity >= $this->taskContext->getMinimumSeverity();
    }

    /**
     * Writes the record as it is: tags in messages are no formatting of the console.
     *
     * @param string $level
     * @param string $message
     *
     * @return void
     */
    protected function write(string $level, string $message): void
    {
        $this->output ??= new ConsoleOutput();
        $this->output->writeln($this->formatLine($level, $message), OutputInterface::OUTPUT_RAW);
    }
}
