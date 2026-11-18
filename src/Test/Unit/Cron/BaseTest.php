<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Test\Unit\Cron;

use Infrangible\Task\Cron\Base;
use Infrangible\Task\Helper\Task as TaskHelper;
use Infrangible\Task\Test\Unit\Task\TestTask;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

/**
 * Tests the cron jobs that run a task.
 */
class BaseTest extends TestCase
{
    /**
     * Returns a cron job whose task has the result and an empty summary.
     *
     * @param bool $result
     *
     * @return Base
     */
    private function createCron(bool $result): Base
    {
        $task = $this->createStub(TestTask::class);
        $task->method('getSummary')->willReturn('');

        $taskHelper = $this->createStub(TaskHelper::class);
        $taskHelper->method('getTask')->willReturn($task);
        $taskHelper->method('launchTask')->willReturn($result);

        return new class ($taskHelper) extends Base {
            /**
             * @return string
             */
            protected function getTaskName(): string
            {
                return 'test_task';
            }

            /**
             * @return string
             */
            protected function getClassName(): string
            {
                return TestTask::class;
            }
        };
    }

    /**
     * Tests a task without summary; before, the cron job failed with a TypeError.
     *
     * @return void
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function testEmptySummary(): void
    {
        $this->assertSame('', $this->createCron(true)->run());
    }

    /**
     * Tests that a failed task fails the cron job.
     *
     * @return void
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function testFailedTask(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The task: test_task failed.');

        $this->createCron(false)->run();
    }
}
