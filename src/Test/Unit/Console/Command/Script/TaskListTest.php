<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Test\Unit\Console\Command\Script;

use Infrangible\Core\Helper\Stores;
use Infrangible\Task\Console\Command\Script\Task;
use Infrangible\Task\Helper\Task as TaskHelper;
use Infrangible\Task\Helper\Values;
use Infrangible\Task\Test\Unit\Task\TestTask;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\Renderer\Placeholder;
use Magento\Store\Model\Store;
use Magento\Store\Model\App\Emulation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests the result of the commands that run tasks.
 */
class TaskListTest extends TestCase
{
    /** @var string[] */
    private array $launches = [];

    /**
     * Restores the renderer of phrases, which the script sets for all phrases.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Phrase::setRenderer(new Placeholder());
    }

    /**
     * Returns the script of a command that runs one task with the results of the runs.
     *
     * @param bool[] $results
     *
     * @return Task
     */
    private function createScript(array $results): Task
    {
        $taskHelper = $this->createStub(TaskHelper::class);
        $taskHelper->method('getTask')->willReturn($this->createStub(TestTask::class));
        $taskHelper->method('launchTask')->willReturnCallback(
            function (TestTask $task, string $storeCode) use (&$results): bool {
                $this->launches[] = $storeCode;

                return (bool)array_shift($results);
            }
        );

        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn('1');

        $stores = $this->createStub(Stores::class);
        $stores->method('getStore')->willReturn($store);

        return new class (
            $taskHelper,
            $this->createStub(Emulation::class),
            new Placeholder(),
            $stores,
            new Values()
        ) extends Task {
            /**
             * @return string
             */
            protected function getTaskName(): string
            {
                return 'test_task';
            }

            /**
             * @return string
             */
            protected function getClassName(): string
            {
                return TestTask::class;
            }
        };
    }

    /**
     * Runs the script with the stores.
     *
     * @param Task $script
     * @param string $storeCodes
     *
     * @return int
     * @throws LocalizedException
     * @throws FileSystemException
     */
    private function execute(Task $script, string $storeCodes): int
    {
        $input = $this->createStub(InputInterface::class);
        $input->method('getOption')->willReturnCallback(
            fn(string $name): ?string => $name === 'store_code' ? $storeCodes : null
        );

        return $script->execute($input, $this->createStub(OutputInterface::class));
    }

    /**
     * Tests that the command succeeds if the task succeeded with every store.
     *
     * @return void
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function testSuccess(): void
    {
        $this->assertSame(Command::SUCCESS, $this->execute($this->createScript([true, true]), 'default, second'));
        $this->assertSame(['default', 'second'], $this->launches);
    }

    /**
     * Tests that the command fails if the task failed with one store; before, it always succeeded.
     *
     * @return void
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function testFailure(): void
    {
        $this->assertSame(Command::FAILURE, $this->execute($this->createScript([false, true]), 'default,second'));
        $this->assertSame(['default', 'second'], $this->launches);
    }
}
