<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Console\Command\Script;

use Infrangible\Core\Console\Command\Script;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Base of the scripts of task commands.
 */
abstract class Base extends Script
{
    /**
     * Returns the store codes of the option --store_code, separated by commas.
     *
     * @param InputInterface $input
     *
     * @return string[]
     */
    protected function getStoreCodes(InputInterface $input): array
    {
        $storeCode = $input->getOption('store_code');

        return array_values(array_filter(array_map('trim', explode(',', is_string($storeCode) ? $storeCode : ''))));
    }

    /**
     * Prepares the task with the options of the command, before it runs.
     *
     * @param \Infrangible\Task\Task\Base $task
     * @param InputInterface $input
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- scripts that need options for their tasks override it
    protected function prepareTask(\Infrangible\Task\Task\Base $task, InputInterface $input): void
    {
    }
}
