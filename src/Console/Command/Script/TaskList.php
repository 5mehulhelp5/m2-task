<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Console\Command\Script;

use Infrangible\Core\Helper\Stores;
use Infrangible\Task\Helper\Task as TaskHelper;
use Infrangible\Task\Helper\Values;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\RendererInterface;
use Magento\Store\Model\App\Emulation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Script of a command that runs several tasks one after another, with each store of the option --store_code. The
 * command fails if one of the tasks failed.
 */
abstract class TaskList extends Base
{
    /**
     * @param TaskHelper $taskHelper
     * @param Emulation $appEmulation
     * @param RendererInterface $renderer
     * @param Stores $storeHelper
     * @param Values $values
     */
    public function __construct(
        protected TaskHelper $taskHelper,
        protected Emulation $appEmulation,
        protected RendererInterface $renderer,
        protected Stores $storeHelper,
        protected Values $values
    ) {
    }

    /**
     * Runs the tasks.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $success = true;
        $logLevel = $input->getOption('log_level');
        $taskId = $input->getOption('id');

        foreach ($this->getStoreCodes($input) as $storeCode) {
            $storeId = $this->values->int($this->storeHelper->getStore($storeCode)->getId());

            $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_ADMINHTML, true);

            try {
                Phrase::setRenderer($this->renderer);

                foreach ($this->getTaskList() as $taskName => $className) {
                    $task = $this->taskHelper->getTask($className);

                    $this->prepareTask($task, $input);

                    $taskSuccess = $this->taskHelper->launchTask(
                        $task,
                        $storeCode,
                        $taskName,
                        is_string($taskId) ? $taskId : null,
                        is_string($logLevel) ? $logLevel : null,
                        (bool)$input->getOption('console'),
                        (bool)$input->getOption('test')
                    );

                    $success = $success && $taskSuccess;
                }
            } finally {
                $this->appEmulation->stopEnvironmentEmulation();
            }
        }

        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Returns the tasks as task name => class.
     *
     * @return array
     * @phpstan-return array<string, string>
     */
    abstract protected function getTaskList(): array;
}
