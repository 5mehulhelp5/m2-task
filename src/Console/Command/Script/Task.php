<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Console\Command\Script;

/**
 * Script of a command that runs one task with each store of the option --store_code. The command fails if the task
 * failed.
 */
abstract class Task extends TaskList
{
    /**
     * Returns the task.
     *
     * @return array
     * @phpstan-return array<string, string>
     */
    protected function getTaskList(): array
    {
        return [$this->getTaskName() => $this->getClassName()];
    }

    /**
     * Returns the name of the task.
     *
     * @return string
     */
    abstract protected function getTaskName(): string;

    /**
     * Returns the class of the task.
     *
     * @return string
     */
    abstract protected function getClassName(): string;
}
