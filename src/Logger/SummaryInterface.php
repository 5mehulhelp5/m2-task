<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Logger;

/**
 * Collects the records of tasks for their summary, separated by task name and task ID.
 */
interface SummaryInterface
{
    /**
     * Returns the records of a task.
     *
     * @param string $taskName
     * @param string $taskId
     *
     * @return Record[]
     */
    public function getRecords(string $taskName, string $taskId): array;

    /**
     * Adds a record to the summary of a task.
     *
     * @param string $taskName
     * @param string $taskId
     * @param Record $record
     *
     * @return void
     */
    public function addRecord(string $taskName, string $taskId, Record $record): void;
}
