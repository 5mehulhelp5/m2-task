<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Test\Unit\Block\Adminhtml\Run;

use Infrangible\Task\Block\Adminhtml\Run\View;
use Infrangible\Task\Logger\LogFiles;
use Infrangible\Task\Model\ResourceModel\Run as RunResource;
use Infrangible\Task\Model\ResourceModel\RunFactory as RunResourceFactory;
use Infrangible\Task\Model\Run;
use Infrangible\Task\Model\RunFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Tests the target of the back button of a run, which comes from the URL, and the log files of a run.
 */
class ViewTest extends TestCase
{
    /**
     * Returns the routes of the parameter and the routes of the button.
     *
     * @return array
     * @phpstan-return array<string, array{0: string|null, 1: string}>
     */
    public static function backRoutes(): array
    {
        return [
            'none'           => [null, 'infrangible_task/run_result/index'],
            'admin route'    => [
                base64_encode('infrangible_sitemap/sitemap/index'),
                'infrangible_sitemap/sitemap/index'
            ],
            'script'         => [base64_encode("x';alert(1);'"), 'infrangible_task/run_result/index'],
            'external url'   => [base64_encode('https://example.com/'), 'infrangible_task/run_result/index'],
            'no base64'      => ['%%%', 'infrangible_task/run_result/index'],
            'too many parts' => [base64_encode('a/b/c/d'), 'infrangible_task/run_result/index']
        ];
    }

    /**
     * Tests that only admin routes are accepted.
     *
     * @dataProvider backRoutes
     *
     * @param string|null $parameter
     * @param string $expectedRoute
     *
     * @return void
     * @throws ReflectionException
     */
    #[DataProvider('backRoutes')]
    public function testBackRoute(?string $parameter, string $expectedRoute): void
    {
        // the constructor of Magento's backend blocks needs the object manager, the route only the data of the block
        $block = (new ReflectionClass(View::class))->newInstanceWithoutConstructor();
        $block->setData('back_route', $parameter);

        $this->assertSame($expectedRoute, $block->getBackRoute());
    }

    /**
     * Tests that a log file that cannot be read shows the error instead of failing the page.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testUnreadableLogFile(): void
    {
        $run = $this->createStub(Run::class);
        $run->method('getId')->willReturn('1');
        $run->method('getData')->willReturn('value');

        $runFactory = $this->createStub(RunFactory::class);
        $runFactory->method('create')->willReturn($run);

        $runResourceFactory = $this->createStub(RunResourceFactory::class);
        $runResourceFactory->method('create')->willReturn($this->createStub(RunResource::class));

        $logFiles = $this->createStub(LogFiles::class);
        $logFiles->method('getPath')->willReturn('/var/log/task/value.log');

        $file = $this->createStub(File::class);
        $file->method('isFile')->willReturn(true);
        $file->method('fileGetContents')->willThrowException(new FileSystemException(__('Cannot read the file')));

        // the constructor of Magento's backend blocks needs the object manager
        $block = (new ReflectionClass(View::class))->newInstanceWithoutConstructor();
        $block->setData('run_id', '1');

        $dependencies = [
            'runFactory'         => $runFactory,
            'runResourceFactory' => $runResourceFactory,
            'logFiles'           => $logFiles,
            'file'               => $file
        ];

        foreach ($dependencies as $property => $value) {
            (new ReflectionProperty(View::class, $property))->setValue($block, $value);
        }

        $this->assertSame('/var/log/task/value.log', $block->getLogFileName());
        $this->assertSame(['Cannot read the file'], $block->getContent());
        $this->assertSame(['Cannot read the file'], $block->getErrorContent());
    }
}
