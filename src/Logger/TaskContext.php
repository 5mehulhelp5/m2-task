<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Logger;

use Infrangible\Core\Helper\Registry;
use Infrangible\Core\Helper\Stores;
use Magento\Framework\Exception\NoSuchEntityException;
use Infrangible\Task\Helper\Values;
use Psr\Log\LogLevel;

/**
 * The task that is running: its name, ID and log settings, which Infrangible\Task\Task\Base registers while it runs.
 */
class TaskContext
{
    /**
     * @param Registry $registryHelper
     * @param Stores $storeHelper
     * @param LogLevels $logLevels
     * @param Values $values
     */
    public function __construct(
        protected Registry $registryHelper,
        protected Stores $storeHelper,
        protected LogLevels $logLevels,
        protected Values $values
    ) {
    }

    /**
     * Returns the log levels.
     *
     * @return LogLevels
     */
    public function getLogLevels(): LogLevels
    {
        return $this->logLevels;
    }

    /**
     * Returns the name of the running task, or an empty string.
     *
     * @return string
     */
    public function getTaskName(): string
    {
        return $this->values->string($this->registryHelper->registry('current_task_name'));
    }

    /**
     * Returns the ID of the running task, or an empty string.
     *
     * @return string
     */
    public function getTaskId(): string
    {
        return $this->values->string($this->registryHelper->registry('current_task_id'));
    }

    /**
     * Checks whether a task is running.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getTaskName() !== '' && $this->getTaskId() !== '';
    }

    /**
     * Returns the code of the store the task runs with.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCode(): string
    {
        return $this->storeHelper->getStore()->getCode();
    }

    /**
     * Returns the lowest severity that is logged.
     *
     * @return int
     */
    public function getMinimumSeverity(): int
    {
        $logLevel = $this->values->string($this->registryHelper->registry('current_task_log_level'));

        return $this->logLevels->getMinimumSeverity($logLevel === '' ? LogLevel::INFO : $logLevel);
    }

    /**
     * Returns the lowest severity that counts as error.
     *
     * @return int
     */
    public function getErrorSeverity(): int
    {
        return $this->logLevels->getErrorSeverity(
            (bool)$this->registryHelper->registry('current_task_log_warn_as_error')
        );
    }

    /**
     * Checks whether the task logs on the console.
     *
     * @return bool
     */
    public function isConsole(): bool
    {
        return (bool)$this->registryHelper->registry('current_task_console');
    }
}
