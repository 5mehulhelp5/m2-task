<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Model\Config\Source;

use Infrangible\Core\Helper\Database;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * The names of the tasks that ran.
 */
class TaskName implements OptionSourceInterface
{
    /**
     * @param Database $databaseHelper
     */
    public function __construct(protected Database $databaseHelper)
    {
    }

    /**
     * Returns the names of the tasks in the table of runs.
     *
     * @return string[]
     */
    protected function getTaskNames(): array
    {
        $query = $this->databaseHelper->select($this->databaseHelper->getTableName('task_run'), ['task_name']);

        $query->distinct();
        $query->order('task_name ASC');

        return array_map(
            fn(mixed $taskName): string => is_scalar($taskName) ? (string)$taskName : '',
            $this->databaseHelper->fetchCol($query)
        );
    }

    /**
     * Returns the task names as options.
     *
     * @return array
     * @phpstan-return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        return array_map(
            static fn(string $taskName): array => ['value' => $taskName, 'label' => $taskName],
            array_values($this->getTaskNames())
        );
    }

    /**
     * Returns the task names as name => name.
     *
     * @return array
     * @phpstan-return array<string, string>
     */
    public function toArray(): array
    {
        $taskNames = $this->getTaskNames();

        return array_combine($taskNames, $taskNames);
    }
}
