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
 * Base of cron jobs that run several tasks one after another with the admin store. If one of them fails, the cron job
 * fails with the summaries as message, after all tasks ran.
 */
abstract class TaskList
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
     * Runs the tasks and returns their summaries.
     *
     * @return string
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function run(): string
    {
        $summaries = [];
        $listSuccess = true;

        foreach ($this->getTaskList() as $taskName => $className) {
            $task = $this->taskHelper->getTask($className);

            $taskSuccess = $this->taskHelper->launchTask(
                $task,
                'admin',
                $taskName,
                date('Y-m-d_H-i-s'),
                null,
                false,
                $this->isTest()
            );

            $listSuccess = $listSuccess && $taskSuccess;
            $summaries[] = $task->getSummary();
        }

        $allSummaries = trim(implode("\n", array_filter($summaries)));

        if (! $listSuccess) {
            throw new LocalizedException(__('At least one task failed. %1', $allSummaries));
        }

        return $allSummaries;
    }

    /**
     * Returns the tasks as task name => class.
     *
     * @return array
     * @phpstan-return array<string, string>
     */
    abstract protected function getTaskList(): array;

    /**
     * Returns whether the tasks run in test mode.
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
