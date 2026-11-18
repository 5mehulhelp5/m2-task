<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Console\Command;

use Infrangible\Core\Console\Command\Command;
use Magento\Framework\App\Area;
use Symfony\Component\Console\Input\InputOption;

/**
 * Base of the commands that run a task: bin/magento task:<task name> with the options --store_code, --id,
 * --log_level, --console and --test.
 */
abstract class Task extends Command
{
    /**
     * Returns the name of the command.
     *
     * @return string
     */
    protected function getCommandName(): string
    {
        return sprintf('task:%s', $this->getTaskName());
    }

    /**
     * Returns the options of the command.
     *
     * @return InputOption[]
     */
    protected function getCommandDefinition(): array
    {
        return [
            new InputOption(
                'store_code',
                null,
                InputOption::VALUE_REQUIRED,
                'Code or codes separated by comma of the store(s) to run task with',
                'admin'
            ),
            // optional: the command of m2-core requires the options with a required value
            new InputOption('id', null, InputOption::VALUE_OPTIONAL, 'Id of the task'),
            new InputOption('log_level', null, InputOption::VALUE_OPTIONAL, 'Log level'),
            new InputOption('console', 'c', InputOption::VALUE_NONE, 'Log on the console'),
            new InputOption('test', 't', InputOption::VALUE_NONE, 'Task runs in test mode')
        ];
    }

    /**
     * Returns the area the command runs in.
     *
     * @return string
     */
    protected function getArea(): string
    {
        return Area::AREA_ADMINHTML;
    }

    /**
     * Returns the name of the task.
     *
     * @return string
     */
    abstract protected function getTaskName(): string;
}
