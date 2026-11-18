<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Test\Unit\Task;

use Error;
use Exception;
use Infrangible\Task\Task\Base;

/**
 * A task for the tests that records its steps and can fail in its preparation or its run.
 */
class TestTask extends Base
{
    /** @var string[] */
    public array $steps = [];

    /** @var Exception|null an exception that the preparation throws */
    public ?Exception $prepareException = null;

    /** @var Error|null an error of PHP (like a TypeError) that the run throws, to test that the task catches it */
    public ?Error $runError = null;

    /** @var bool */
    public bool $runResult = true;

    /**
     * Records the step, or throws the exception.
     *
     * @return void
     * @throws Exception
     */
    protected function prepare(): void
    {
        $this->steps[] = 'prepare';

        if ($this->prepareException !== null) {
            throw $this->prepareException;
        }
    }

    /**
     * Records the step, or throws the error.
     *
     * @return bool
     */
    protected function runTask(): bool
    {
        $this->steps[] = 'run';

        if ($this->runError !== null) {
            throw $this->runError;
        }

        return $this->runResult;
    }

    /**
     * Records the step with the success.
     *
     * @param bool $success
     *
     * @return void
     */
    protected function dismantle(bool $success): void
    {
        $this->steps[] = 'dismantle:' . ($success ? 'success' : 'failure');
    }

    /**
     * Returns false.
     *
     * @return bool
     */
    public function isEmptyRun(): bool
    {
        return false;
    }
}
