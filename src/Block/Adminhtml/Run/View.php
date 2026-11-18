<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Block\Adminhtml\Run;

use Infrangible\Task\Logger\LogFiles;
use Infrangible\Task\Model\ResourceModel\RunFactory as RunResourceFactory;
use Infrangible\Task\Model\Run;
use Infrangible\Task\Model\RunFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\ButtonFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;

/**
 * Shows the log file and the error log file of a run.
 */
class View extends Template
{
    /** Route of the list of runs, the default target of the back button. */
    private const INDEX_ROUTE = 'infrangible_task/run_result/index';

    /** @var string */
    protected $_template = 'Infrangible_Task::infrangible/task/run.phtml';

    /** @var Run|null */
    private ?Run $run = null;

    /**
     * @param Context $context
     * @param RunFactory $runFactory
     * @param RunResourceFactory $runResourceFactory
     * @param ButtonFactory $buttonFactory
     * @param LogFiles $logFiles
     * @param File $file
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        protected RunFactory $runFactory,
        protected RunResourceFactory $runResourceFactory,
        protected ButtonFactory $buttonFactory,
        protected LogFiles $logFiles,
        protected File $file,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Returns the run of the parameter run_id.
     *
     * @return Run
     */
    protected function getRun(): Run
    {
        if ($this->run === null) {
            $this->run = $this->runFactory->create();

            $runId = $this->getData('run_id');

            if (is_scalar($runId) && (string)$runId !== '') {
                $this->runResourceFactory->create()->load($this->run, $runId);
            }
        }

        return $this->run;
    }

    /**
     * Returns the path of the log file of the run, or an empty string if there is no run.
     *
     * @return string
     */
    public function getLogFileName(): string
    {
        return $this->getFileName(LogFiles::LOG);
    }

    /**
     * Returns the path of the error log file of the run, or an empty string if there is no run.
     *
     * @return string
     */
    public function getErrorFileName(): string
    {
        return $this->getFileName(LogFiles::ERROR);
    }

    /**
     * Returns the lines of the log file, or a message if it does not exist or cannot be read.
     *
     * @return string[]
     */
    public function getContent(): array
    {
        $lines = $this->getLines($this->getLogFileName());

        return $lines ?? [__('Log file does not exist at: %1', $this->getLogFileName())->render()];
    }

    /**
     * Returns the lines of the error log file, none if it does not exist, or a message if it cannot be read.
     *
     * @return string[]
     */
    public function getErrorContent(): array
    {
        return $this->getLines($this->getErrorFileName()) ?? [];
    }

    /**
     * Returns the button back to the page that showed the link, or to the list of runs.
     *
     * @return string
     */
    public function getBackButtonHtml(): string
    {
        $backButton = $this->buttonFactory->create(
            [
                'data' => [
                    'id'      => 'back',
                    'title'   => __('Back'),
                    'label'   => __('Back'),
                    'class'   => 'back',
                    'onclick' => sprintf(
                        "window.location.href = '%s';",
                        $this->_escaper->escapeJs($this->getUrl($this->getBackRoute()))
                    )
                ]
            ]
        );

        return $backButton->toHtml();
    }

    /**
     * Returns the route of the back button.
     *
     * The base64 encoded route of the parameter back_route, if it is an admin route (route/controller/action),
     * otherwise the list of runs.
     *
     * @return string
     */
    public function getBackRoute(): string
    {
        $backRoute = $this->getData('back_route');
        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- the route is a parameter of the URL
        $backRoute = is_string($backRoute) ? base64_decode($backRoute, true) : false;

        return is_string($backRoute) && preg_match('/^[a-z0-9_]+(\/[a-z0-9_]+){0,2}$/i', $backRoute) ?
            $backRoute : self::INDEX_ROUTE;
    }

    /**
     * Returns the path of a log file of the run, or an empty string if there is no run or no log directory.
     *
     * @param string $extension
     *
     * @return string
     */
    private function getFileName(string $extension): string
    {
        $run = $this->getRun();

        if (! $run->getId()) {
            return '';
        }

        try {
            return $this->logFiles->getPath(
                $this->stringValue($run->getData('task_name')),
                $this->stringValue($run->getData('store_code')),
                $this->stringValue($run->getData('task_id')),
                $extension
            );
        } catch (FileSystemException) {
            return '';
        }
    }

    /**
     * Returns the lines of a file, null if it does not exist, or the error if it cannot be read.
     *
     * @param string $fileName
     *
     * @return string[]|null
     */
    private function getLines(string $fileName): ?array
    {
        try {
            if ($fileName === '' || ! $this->file->isFile($fileName)) {
                return null;
            }

            return array_map('trim', explode("\n", trim($this->file->fileGetContents($fileName))));
        } catch (FileSystemException $exception) {
            return [$exception->getMessage()];
        }
    }

    /**
     * Returns a value of the run as string.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
