<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Model\ResourceModel\Run;

use Infrangible\Task\Model\Run;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection of runs.
 */
class Collection extends AbstractCollection
{
    /**
     * Initializes the model and the resource model.
     *
     * @return void
     */
    public function _construct(): void
    {
        $this->_init(Run::class, \Infrangible\Task\Model\ResourceModel\Run::class);
    }

    /**
     * Counts without the HAVING conditions of the calculated fields of the grid.
     *
     * @return Select
     */
    public function getSelectCountSql(): Select
    {
        $selectCount = parent::getSelectCountSql();

        $selectCount->reset(Select::HAVING);

        return $selectCount;
    }

    /**
     * Filters the runs that have not finished.
     *
     * @return void
     * @throws LocalizedException
     */
    public function addIsRunningFilter(): void
    {
        $this->addFieldToFilter('finish_at', ['null' => true]);
    }

    /**
     * Filters the runs that started before the number of days.
     *
     * @param int $days
     *
     * @return void
     * @throws LocalizedException
     */
    public function addStartAtFilter(int $days = 90): void
    {
        // the start dates are stored in UTC
        $this->addFieldToFilter('start_at', ['lt' => gmdate('Y-m-d H:i:s', time() - $days * 86400)]);
    }
}
