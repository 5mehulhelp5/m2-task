<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Cron;

use Infrangible\Task\Helper\Task;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Base of cron jobs that run a task with the admin store. A failed task fails the cron job with its summary as message.
 */
abstract class Base
{
    /** @var bool */
    private bool $test = false;

    /**
     * @param Task $taskHelper
     */
    public function __construct(protected Task $taskHelper)
    {
    }

    /**
     * Runs the task and returns its summary.
     *
     * @return string
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function run(): string
    {
        $taskName = $this->getTaskName();

        if ($taskName === '') {
            throw new LocalizedException(__('Please specify a task name!'));
        }

        $task = $this->taskHelper->getTask($this->getClassName());

        $taskSuccess = $this->taskHelper->launchTask(
            $task,
            'admin',
            $taskName,
            date('Y-m-d_H-i-s'),
            null,
            false,
            $this->isTest()
        );

        if (! $taskSuccess) {
            throw new LocalizedException(__('The task: %1 failed. %2', $taskName, $task->getSummary()));
        }

        return $task->getSummary();
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

    /**
     * Returns whether the task runs in test mode.
     *
     * @return bool
     */
    public function isTest(): bool
    {
        return $this->test;
    }

    /**
     * Switches the test mode.
     *
     * @param bool $test
     *
     * @return void
     */
    protected function setTestMode(bool $test = true): void
    {
        $this->test = $test;
    }
}
