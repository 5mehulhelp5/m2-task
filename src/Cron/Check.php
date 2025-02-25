<?php

declare(strict_types=1);

namespace Infrangible\Task\Cron;

use Infrangible\Task\Model\ResourceModel\Run\CollectionFactory;
use Infrangible\Task\Model\ResourceModel\RunFactory;
use Infrangible\Task\Model\Run;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Check
{
    /** @var CollectionFactory */
    protected $runCollectionFactory;

    /** @var RunFactory $runResourceFactory */
    protected $runResourceFactory;

    /**
     * @param CollectionFactory $runCollectionFactory
     * @param RunFactory        $runResourceFactory
     */
    public function __construct(CollectionFactory $runCollectionFactory, RunFactory $runResourceFactory)
    {
        $this->runCollectionFactory = $runCollectionFactory;
        $this->runResourceFactory = $runResourceFactory;
    }

    /**
     * @throws AlreadyExistsException
     */
    public function execute()
    {
        $collection = $this->runCollectionFactory->create();

        $collection->addIsRunningFilter();

        $runResource = $this->runResourceFactory->create();

        /** @var Run $run */
        foreach ($collection as $run) {
            $processId = $run->getProcessId();

            if (!empty($processId)) {
                if (is_callable('shell_exec') && false === stripos(ini_get('disable_functions'), 'shell_exec')) {
                    $count = trim(shell_exec(sprintf('ps -p %d -o pid= | wc -l', $processId)));

                    if ($count > 0) {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            $run->setFinishAt(gmdate('Y-m-d H:i:s'));

            $runResource->save($run);
        }
    }
}
