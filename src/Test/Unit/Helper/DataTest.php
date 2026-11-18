<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Test\Unit\Helper;

use FeWeDev\Base\Variables;
use Infrangible\Task\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests which configuration values a task uses: its own or the general ones of task_general.
 */
class DataTest extends TestCase
{
    /**
     * Returns the helper with the configuration values.
     *
     * @param array $values
     * @phpstan-param array<string, mixed> $values
     *
     * @return Data
     */
    private function createHelper(array $values): Data
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn(string $path): mixed => $values[ $path ] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            fn(string $path): bool => (bool)($values[ $path ] ?? false)
        );

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);

        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new Data($scopeConfig, $storeManager, new Variables());
    }

    /**
     * Tests that general values are used unless the task overwrites the group.
     *
     * @return void
     */
    public function testGeneralValues(): void
    {
        $values = [
            'task_general/settings/max_memory' => '512',
            'task_import/settings/max_memory'  => '2048',
            'task_general/logging/log_level'   => 'info',
            'task_import/logging/log_level'    => 'debug'
        ];

        $helper = $this->createHelper($values);

        $this->assertSame('512', $helper->getTaskConfigValue('import', 'settings', 'max_memory'));
        $this->assertSame('info', $helper->getTaskConfigValue('import', 'logging', 'log_level'));

        $values[ 'task_import/settings/overwrite_task_general' ] = '1';
        $values[ 'task_import/logging/overwrite_task_general' ] = '1';

        $helper = $this->createHelper($values);

        $this->assertSame('2048', $helper->getTaskConfigValue('import', 'settings', 'max_memory'));
        $this->assertSame('debug', $helper->getTaskConfigValue('import', 'logging', 'log_level'));
    }

    /**
     * Tests that settings of the task only (for example the path of a file task) are always the ones of the task.
     *
     * @return void
     */
    public function testTaskSettings(): void
    {
        $helper = $this->createHelper(
            ['task_import/settings/path' => 'var/import', 'task_import/data/title' => 'Import']
        );

        $this->assertSame('var/import', $helper->getTaskConfigValue('import', 'settings', 'path'));
        $this->assertSame('Import', $helper->getTaskConfigValue('import', 'data', 'title'));
        $this->assertSame('none', $helper->getTaskConfigValue('import', 'settings', 'archive_path', 'none'));
    }

    /**
     * Tests flags.
     *
     * @return void
     */
    public function testFlags(): void
    {
        $helper = $this->createHelper(['task_general/summary_error/send' => '0']);

        $this->assertFalse($helper->getTaskConfigValue('import', 'summary_error', 'send', true, true));
        $this->assertTrue($helper->getTaskConfigValue('import', 'summary_success', 'send', true, true));
    }
}
