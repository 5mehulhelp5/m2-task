<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Test\Unit\Logger;

use FilesystemIterator;
use Infrangible\Core\Helper\Registry;
use Infrangible\Core\Helper\Stores;
use Infrangible\Task\Helper\Values;
use Infrangible\Task\Logger\ErrorLog;
use Infrangible\Task\Logger\Log;
use Infrangible\Task\Logger\LogFiles;
use Infrangible\Task\Logger\LogLevels;
use Infrangible\Task\Logger\Record;
use Infrangible\Task\Logger\Summary\All;
use Infrangible\Task\Logger\Summary\Error;
use Infrangible\Task\Logger\Summary\Success;
use Infrangible\Task\Logger\TaskContext;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Tests the loggers of tasks: which records they take with the log settings of the task, that they separate tasks and
 * that they log nothing without a running task.
 */
class LoggersTest extends TestCase
{
    /** @var Registry */
    private Registry $registry;

    /** @var TaskContext */
    private TaskContext $taskContext;

    /** @var string */
    private string $logPath = '';

    /**
     * Creates the context of the tasks with a store and a temporary log directory.
     *
     * @return void
     */
    protected function setUp(): void
    {
        /** @noinspection PhpDeprecationInspection the registry helper of m2-core works with Magento's registry */
        $this->registry = new Registry(new \Magento\Framework\Registry());
        $this->logPath = sys_get_temp_dir() . '/task-log-test-' . uniqid();

        $store = $this->createStub(Store::class);
        $store->method('getCode')->willReturn('default');

        $stores = $this->createStub(Stores::class);
        $stores->method('getStore')->willReturn($store);

        $this->taskContext = new TaskContext($this->registry, $stores, new LogLevels(), new Values());
    }

    /**
     * Removes the log directory with its files.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (! is_dir($this->logPath)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->logPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
        }

        rmdir($this->logPath);
    }

    /**
     * Registers a running task like Infrangible\Task\Task\Base::init().
     *
     * @param string $taskId
     * @param string $logLevel
     * @param bool $warningAsError
     *
     * @return void
     */
    private function startTask(string $taskId, string $logLevel = LogLevel::INFO, bool $warningAsError = false): void
    {
        $this->registry->register('current_task_name', 'test_task', false, true);
        $this->registry->register('current_task_id', $taskId, false, true);
        $this->registry->register('current_task_log_level', $logLevel, false, true);
        $this->registry->register('current_task_log_warn_as_error', $warningAsError ? 1 : 0, false, true);
    }

    /**
     * Returns the messages of records.
     *
     * @param Record[] $records
     *
     * @return string[]
     */
    private function getMessages(array $records): array
    {
        return array_map(fn(Record $record): string => $record->getMessage(), $records);
    }

    /**
     * Logs a record of each level into the summaries.
     *
     * @return array{0: All, 1: Success, 2: Error}
     */
    private function logIntoSummaries(): array
    {
        $summaries = [
            new All($this->taskContext, $this->registry),
            new Success($this->taskContext, $this->registry),
            new Error($this->taskContext, $this->registry)
        ];

        foreach ($summaries as $summary) {
            $summary->debug('debug');
            $summary->info('info {value}', ['value' => 42]);
            $summary->warning('warning');
            $summary->error('error');
        }

        return $summaries;
    }

    /**
     * Tests the records of the summaries with the log level info.
     *
     * @return void
     */
    public function testSummaries(): void
    {
        $this->startTask('run-1');

        [$all, $success, $error] = $this->logIntoSummaries();

        $this->assertSame(
            ['info 42', 'warning', 'error'],
            $this->getMessages($all->getRecords('test_task', 'run-1'))
        );
        $this->assertSame(['info 42', 'warning'], $this->getMessages($success->getRecords('test_task', 'run-1')));
        $this->assertSame(['error'], $this->getMessages($error->getRecords('test_task', 'run-1')));
        $this->assertSame($all, $this->registry->registry('task_summary_all'));
    }

    /**
     * Tests that warnings count as errors, if configured, and that errors are collected whatever log level is set.
     *
     * @return void
     */
    public function testWarningAsErrorAndLogLevel(): void
    {
        $this->startTask('run-1', LogLevel::CRITICAL, true);

        [$all, $success, $error] = $this->logIntoSummaries();

        $this->assertSame([], $this->getMessages($all->getRecords('test_task', 'run-1')));
        $this->assertSame([], $this->getMessages($success->getRecords('test_task', 'run-1')));
        $this->assertSame(['warning', 'error'], $this->getMessages($error->getRecords('test_task', 'run-1')));
    }

    /**
     * Tests that the records of tasks are kept apart.
     *
     * @return void
     */
    public function testTasksAreSeparated(): void
    {
        $all = new All($this->taskContext, $this->registry);

        $this->startTask('run-1');
        $all->info('first');

        $this->startTask('run-2');
        $all->info('second');

        $this->assertSame(['first'], $this->getMessages($all->getRecords('test_task', 'run-1')));
        $this->assertSame(['second'], $this->getMessages($all->getRecords('test_task', 'run-2')));
    }

    /**
     * Tests that nothing is logged without a running task, for example while Magento is installed.
     *
     * @return void
     */
    public function testNoTask(): void
    {
        $all = new All($this->taskContext, $this->registry);

        $all->error('error');

        $this->assertFalse($this->taskContext->isActive());
        $this->assertSame([], $all->getRecords('', ''));
    }

    /**
     * Tests the log file and the error log file of a run.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testLogFiles(): void
    {
        $this->startTask('run/1');

        $directoryList = $this->createStub(DirectoryList::class);
        $directoryList->method('getPath')->willReturn($this->logPath);

        $logFiles = new LogFiles($directoryList);

        $log = new Log($this->taskContext, $logFiles, new File());
        $errorLog = new ErrorLog($this->taskContext, $logFiles, new File());

        foreach ([$log, $errorLog] as $logger) {
            $logger->info('info');
            $logger->error('error');
        }

        $logFile = $logFiles->getPath('test_task', 'default', 'run/1', LogFiles::LOG);
        $errorFile = $logFiles->getPath('test_task', 'default', 'run/1', LogFiles::ERROR);

        $this->assertSame($this->logPath . '/task/test_task/default/run_1.log', $logFile);
        $this->assertStringEndsWith('INFO: info', trim((string)file_get_contents($logFile)));
        $this->assertStringEndsWith('ERROR: error', trim((string)file_get_contents($errorFile)));
    }

    /**
     * Tests the severities of the log levels.
     *
     * @return void
     */
    public function testLogLevels(): void
    {
        $logLevels = new LogLevels();

        $this->assertSame(PHP_INT_MAX, $logLevels->getMinimumSeverity('off'));
        $this->assertSame($logLevels->getSeverity(LogLevel::INFO), $logLevels->getSeverity('unknown'));
        $this->assertSame(LogLevel::WARNING, $logLevels->getName('WARNING'));
        $this->assertSame($logLevels->getSeverity(LogLevel::WARNING), $logLevels->getErrorSeverity(true));
        $this->assertSame($logLevels->getSeverity(LogLevel::ERROR), $logLevels->getErrorSeverity(false));
    }
}
