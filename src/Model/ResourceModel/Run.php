<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * The runs in the table task_run.
 */
class Run extends AbstractDb
{
    /**
     * Initializes the table.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('task_run', 'run_id');
    }
}
