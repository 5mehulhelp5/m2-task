<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Test\Unit\Task;

use Infrangible\Core\Helper\Registry;
use Infrangible\SimpleMail\Model\MailFactory;
use Infrangible\Task\Helper\Data;
use Infrangible\Task\Model\ResourceModel\Run as RunResource;
use Infrangible\Task\Model\ResourceModel\RunFactory as RunResourceFactory;
use Infrangible\Task\Model\Run;
use Infrangible\Task\Model\RunFactory;
use Infrangible\Task\Test\Unit\RecordingLogger;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use TypeError;

/**
 * Tests the run of a task: its steps, the handling of errors, the lock against parallel runs and the run record.
 */
class BaseTest extends TestCase
{
    /** @var string */
    private string $tempPath = '';

    /** @var RecordingLogger */
    private RecordingLogger $logger;

    /** @var Registry */
    private Registry $registry;

    /**
     * @var array
     * @phpstan-var array<int, array{0: int, 1: bool, 2: bool}>
     */
    private array $finishedRuns = [];

    /**
     * @var array
     * @phpstan-var array<string, mixed>
     */
    private array $settings = [];

    /**
     * Creates a temporary directory for the lock files.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->tempPath = sys_get_temp_dir() . '/task-test-' . uniqid();
        $this->logger = new RecordingLogger();
        /** @noinspection PhpDeprecationInspection the registry helper of m2-core works with Magento's registry */
        $this->registry = new Registry(new \Magento\Framework\Registry());
    }

    /**
     * Removes the lock files.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (glob($this->tempPath . '/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->tempPath)) {
            rmdir($this->tempPath);
        }
    }

    /**
     * Returns a task with the settings of the test.
     *
     * @return TestTask
     */
    private function createTask(): TestTask
    {
        $helper = $this->createStub(Data::class);
        $helper->method('getTaskConfigValue')->willReturnCallback(
            fn(string $taskName, string $section, string $field, mixed $defaultValue = null): mixed =>
                $this->settings[ $section . '/' . $field ] ?? $defaultValue
        );

        $directoryList = $this->createStub(DirectoryList::class);
        $directoryList->method('getPath')->willReturn($this->tempPath);

        $run = $this->createStub(Run::class);
        $run->method('finish')->willReturnCallback(
            function (int $maxMemoryUsage, bool $success, bool $emptyRun): void {
                $this->finishedRuns[] = [$maxMemoryUsage, $success, $emptyRun];
            }
        );

        $runFactory = $this->createStub(RunFactory::class);
        $runFactory->method('create')->willReturn($run);

        $runResourceFactory = $this->createStub(RunResourceFactory::class);
        $runResourceFactory->method('create')->willReturn($this->createStub(RunResource::class));

        $task = new TestTask(
            new File(),
            $this->registry,
            $helper,
            $this->logger,
            $directoryList,
            $runFactory,
            $runResourceFactory,
            $this->createStub(MailFactory::class)
        );

        $task->init('admin', 'test_task', 'run-1');

        return $task;
    }

    /**
     * Tests a successful run: all steps, the record of the run and the running task in the registry.
     *
     * @return void
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function testSuccessfulRun(): void
    {
        $task = $this->createTask();

        $this->assertSame('test_task', $this->registry->registry('current_task_name'));
        $this->assertTrue($task->launch());
        $this->assertSame(['prepare', 'run', 'dismantle:success'], $task->steps);
        $this->assertCount(1, $this->finishedRuns);
        $this->assertTrue($this->finishedRuns[ 0 ][ 1 ]);
        $this->assertGreaterThan(0, $this->finishedRuns[ 0 ][ 0 ]);
        $this->assertContains('Running task: test_task', $this->logger->getMessages(LogLevel::INFO));
        $this->assertNull($this->registry->registry('current_task_name'));
    }

    /**
     * Tests that an error of PHP in the task (not only exceptions) fails the run, which is still dismantled and
     * finished.
     *
     * @return void
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function testErrorInTask(): void
    {
        $task = $this->createTask();
        $task->runError = new TypeError('Wrong type');

        $this->assertFalse($task->launch());
        $this->assertSame(['prepare', 'run', 'dismantle:failure'], $task->steps);
        $this->assertFalse($this->finishedRuns[ 0 ][ 1 ]);
        $this->assertContains(
            'Could not run task because: Wrong type',
            $this->logger->getMessages(LogLevel::ERROR)
        );
    }

    /**
     * Tests that a task that could not be prepared does not run.
     *
     * @return void
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function testFailedPreparation(): void
    {
        $task = $this->createTask();
        $task->prepareException = new LocalizedException(__('No path'));

        $this->assertFalse($task->launch());
        $this->assertSame(['prepare', 'dismantle:failure'], $task->steps);
    }

    /**
     * Tests that a task does not run while another run of it holds the lock.
     *
     * @return void
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function testRunningTaskBlocks(): void
    {
        $task = $this->createTask();

        $lock = $this->holdLock('test_task');

        $this->assertFalse($task->launch());
        $this->assertSame([], $task->steps);
        $this->assertFalse($this->finishedRuns[ 0 ][ 1 ]);
        $this->assertContains(
            'The task: test_task is still running and blocks the process of this task: test_task.',
            $this->logger->getMessages(LogLevel::ERROR)
        );

        fclose($lock);
    }

    /**
     * Tests that a task does not run while a task it depends on runs, and runs once it finished.
     *
     * @return void
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function testDependencyBlocks(): void
    {
        $this->settings[ 'settings/depends_on' ] = 'other_task; ';

        $task = $this->createTask();

        $lock = $this->holdLock('other_task');

        $this->assertFalse($task->launch());
        $this->assertSame([], $task->steps);

        fclose($lock);

        $task->init('admin', 'test_task', 'run-2');

        $this->assertTrue($task->launch());
        $this->assertSame(['prepare', 'run', 'dismantle:success'], $task->steps);
    }

    /**
     * Tests that the admin store can be forbidden.
     *
     * @return void
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function testAdminStoreNotAllowed(): void
    {
        $task = $this->createTask();
        $task->setAllowAdminStore(false);

        $this->expectException(LocalizedException::class);

        $task->launch();
    }

    /**
     * Tests that the summary is empty without summary loggers.
     *
     * @return void
     */
    public function testEmptySummary(): void
    {
        $task = $this->createTask();

        $this->assertSame('', $task->getSummary());
        $this->assertSame([], $task->getSummaryRecords());
    }

    /**
     * Locks a task like another process.
     *
     * @param string $taskName
     *
     * @return resource
     */
    private function holdLock(string $taskName)
    {
        if (! is_dir($this->tempPath)) {
            mkdir($this->tempPath, 0777, true);
        }

        $lock = fopen(sprintf('%s/task_%s.lock', $this->tempPath, $taskName), 'c');

        $this->assertIsResource($lock);
        $this->assertTrue(flock($lock, LOCK_EX | LOCK_NB));

        return $lock;
    }
}
