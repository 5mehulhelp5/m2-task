<?php

declare(strict_types=1);

namespace Infrangible\Task\Console\Command\Script;

use Magento\Framework\App\Area;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\RendererInterface;
use Magento\Store\Model\App\Emulation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class TaskList extends Base
{
    /** @var \Infrangible\Task\Helper\Task */
    protected $taskHelper;

    /** @var Emulation */
    protected $appEmulation;

    /** @var RendererInterface */
    protected $renderer;

    public function __construct(
        \Infrangible\Task\Helper\Task $taskHelper,
        Emulation $appEmulation,
        RendererInterface $renderer
    ) {
        $this->taskHelper = $taskHelper;
        $this->appEmulation = $appEmulation;
        $this->renderer = $renderer;
    }

    /**
     * Executes the current command.
     *
     * @return int 0 if everything went fine, or an error code
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeCodes = $this->getStoreCodes($input);

        $listSuccess = true;

        foreach ($storeCodes as $storeCode) {
            $this->appEmulation->startEnvironmentEmulation(
                $storeCode,
                Area::AREA_ADMINHTML,
                true
            );

            Phrase::setRenderer($this->renderer);

            foreach ($this->getTaskList() as $taskName => $className) {
                $task = $this->taskHelper->getTask($className);

                $this->prepareTask(
                    $task,
                    $input
                );

                $taskSuccess = $this->taskHelper->launchTask(
                    $task,
                    $storeCode,
                    $taskName,
                    null,
                    $input->getOption('log_level'),
                    $input->getOption('console'),
                    $input->getOption('test')
                );

                $listSuccess = $listSuccess && $taskSuccess;
            }

            $this->appEmulation->stopEnvironmentEmulation();
        }

        return $listSuccess ? Command::SUCCESS : Command::FAILURE;
    }

    abstract protected function getTaskList(): array;
}
