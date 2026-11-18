<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Helper;

use Infrangible\Core\Helper\Instances;
use Infrangible\Task\Task\Base;
use LogicException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Creates and runs tasks.
 */
class Task
{
    /**
     * @param Instances $instanceHelper
     */
    public function __construct(protected Instances $instanceHelper)
    {
    }

    /**
     * Creates and runs a task.
     *
     * @param string $storeCode
     * @param string $taskName
     * @param string $className
     * @param string|null $taskId the time, if empty
     * @param string|null $logLevel the configured log level, if empty
     * @param bool $console
     * @param bool $test
     *
     * @return bool
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function runTask(
        string $storeCode,
        string $taskName,
        string $className,
        ?string $taskId = null,
        ?string $logLevel = null,
        bool $console = false,
        bool $test = false
    ): bool {
        if ($taskName === '') {
            throw new LocalizedException(__('Please specify a task name!'));
        }

        $task = $this->getTask($className);

        return $this->launchTask($task, $storeCode, $taskName, $taskId, $logLevel, $console, $test);
    }

    /**
     * Runs a task.
     *
     * @param Base $task
     * @param string $storeCode
     * @param string $taskName
     * @param string|null $taskId the time, if empty
     * @param string|null $logLevel the configured log level, if empty
     * @param bool $console
     * @param bool $test
     *
     * @return bool
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function launchTask(
        Base $task,
        string $storeCode,
        string $taskName,
        ?string $taskId = null,
        ?string $logLevel = null,
        bool $console = false,
        bool $test = false
    ): bool {
        $task->init(
            $storeCode,
            $taskName,
            $taskId === null || $taskId === '' ? date('Y-m-d_H-i-s') : $taskId,
            $logLevel,
            $console,
            $test
        );

        return $task->launch();
    }

    /**
     * Creates a task.
     *
     * @param string $className
     *
     * @return Base
     */
    public function getTask(string $className): Base
    {
        $task = $this->instanceHelper->getInstance($className);

        if (! $task instanceof Base) {
            throw new LogicException(sprintf('Task must extend class: %s', Base::class));
        }

        return $task;
    }
}
