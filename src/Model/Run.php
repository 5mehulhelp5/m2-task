<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;

/**
 * A run of a task, from its start until it finished.
 */
class Run extends AbstractModel
{
    /**
     * Initializes the resource model.
     *
     * @return void
     * @throws LocalizedException
     */
    public function _construct(): void
    {
        $this->_init(ResourceModel\Run::class);
    }

    /**
     * Starts the run with the process of PHP.
     *
     * @param string $storeCode
     * @param string $taskName
     * @param string $taskId
     * @param bool $test
     *
     * @return void
     */
    public function start(string $storeCode, string $taskName, string $taskId, bool $test): void
    {
        $processId = getmypid();

        $this->setData('store_code', $storeCode);
        $this->setData('task_name', $taskName);
        $this->setData('task_id', $taskId);
        $this->setData('process_id', $processId === false ? 0 : $processId);
        $this->setData('test', $test ? 1 : 0);
        $this->setData('start_at', gmdate('Y-m-d H:i:s'));
    }

    /**
     * Finishes the run.
     *
     * @param int $maxMemoryUsage in MB
     * @param bool $success
     * @param bool $emptyRun
     *
     * @return void
     */
    public function finish(int $maxMemoryUsage, bool $success, bool $emptyRun): void
    {
        $this->setData('max_memory_usage', $maxMemoryUsage);
        $this->setData('success', $success ? 1 : 0);
        $this->setData('empty_run', $emptyRun ? 1 : 0);
        $this->setData('finish_at', gmdate('Y-m-d H:i:s'));
    }
}
