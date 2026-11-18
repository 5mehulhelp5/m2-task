<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Plugin;

use Infrangible\Log\Logger\Wrapper;
use Infrangible\Task\Logger\ConsoleLog;
use Infrangible\Task\Logger\ErrorLog;
use Infrangible\Task\Logger\Log;
use Infrangible\Task\Logger\Summary\All;
use Infrangible\Task\Logger\Summary\Error;
use Infrangible\Task\Logger\Summary\Success;

/**
 * Adds the loggers of tasks to the logger of Magento: log files of the run, the console and the summaries.
 */
class Logging
{
    /**
     * @param Log $taskLog
     * @param ErrorLog $taskErrorLog
     * @param ConsoleLog $taskConsoleLog
     * @param All $taskSummaryAll
     * @param Success $taskSummarySuccess
     * @param Error $taskSummaryError
     */
    public function __construct(
        protected Log $taskLog,
        protected ErrorLog $taskErrorLog,
        protected ConsoleLog $taskConsoleLog,
        protected All $taskSummaryAll,
        protected Success $taskSummarySuccess,
        protected Error $taskSummaryError
    ) {
    }

    /**
     * Adds the loggers after Magento's logger.
     *
     * @param Wrapper $wrapper
     *
     * @return void
     */
    public function afterInitialize(Wrapper $wrapper): void
    {
        $wrapper->addLoggers(
            [
                $this->taskLog,
                $this->taskErrorLog,
                $this->taskConsoleLog,
                $this->taskSummaryAll,
                $this->taskSummarySuccess,
                $this->taskSummaryError
            ]
        );
    }
}
