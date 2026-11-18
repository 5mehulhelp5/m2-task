<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Logger;

use Infrangible\Core\Helper\Registry;

/**
 * Collects the records of tasks for a summary, which the task sends by mail and the admin shows after a run.
 *
 * The summaries are registered as "task_summary_<type>" (all, success, error), where the tasks find them.
 */
abstract class Summary extends TaskLogger implements SummaryInterface
{
    /**
     * @var array
     * @phpstan-var array<string, Record[]>
     */
    private array $records = [];

    /**
     * @param TaskContext $taskContext
     * @param Registry $registryHelper
     */
    public function __construct(TaskContext $taskContext, Registry $registryHelper)
    {
        parent::__construct($taskContext);

        $registryHelper->register('task_summary_' . $this->getType(), $this, false, true);
    }

    /**
     * Returns the type of the summary.
     *
     * @return string
     */
    abstract public function getType(): string;

    /**
     * Returns the records of a task.
     *
     * @param string $taskName
     * @param string $taskId
     *
     * @return Record[]
     */
    public function getRecords(string $taskName, string $taskId): array
    {
        return $this->records[ $this->getKey($taskName, $taskId) ] ?? [];
    }

    /**
     * Adds a record to the summary of a task.
     *
     * @param string $taskName
     * @param string $taskId
     * @param Record $record
     *
     * @return void
     */
    public function addRecord(string $taskName, string $taskId, Record $record): void
    {
        $this->records[ $this->getKey($taskName, $taskId) ][] = $record;
    }

    /**
     * Adds the record to the summary of the running task.
     *
     * @param string $level
     * @param string $message
     *
     * @return void
     */
    protected function write(string $level, string $message): void
    {
        $this->addRecord(
            $this->taskContext->getTaskName(),
            $this->taskContext->getTaskId(),
            new Record($level, $message)
        );
    }

    /**
     * Returns the key of the records of a task, which separates tasks that run one after another.
     *
     * @param string $taskName
     * @param string $taskId
     *
     * @return string
     */
    private function getKey(string $taskName, string $taskId): string
    {
        return $taskName . '/' . $taskId;
    }
}
