<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Test\Unit\Plugin;

use Infrangible\Task\Plugin\Config\Model\Config\Structure\Data;
use PHPUnit\Framework\TestCase;

/**
 * Tests the groups that every task section gets.
 */
class ConfigStructureDataTest extends TestCase
{
    /**
     * Returns the configuration with the section of a task and a field of the task.
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    private function getConfig(): array
    {
        return [
            'config' => [
                'system' => [
                    'sections' => [
                        'task_import' => [
                            'tab'      => 'tasks',
                            'children' => [
                                'settings' => [
                                    'children' => ['path' => ['id' => 'path', 'sortOrder' => 1]]
                                ]
                            ]
                        ],
                        'catalog'     => ['tab' => 'catalog']
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns a nested array of the configuration.
     *
     * @param mixed $data
     * @param string ...$keys
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    private function getArray(mixed $data, string ...$keys): array
    {
        foreach ($keys as $key) {
            $this->assertIsArray($data);
            $data = $data[ $key ] ?? null;
        }

        $this->assertIsArray($data);

        return $data;
    }

    /**
     * Tests the fields of a task: sorted, with their IDs as keys, and depending on overwriting the general settings.
     *
     * @return void
     */
    public function testTaskSection(): void
    {
        $plugin = new Data();

        [$config] = $plugin->beforeMerge(
            $this->createStub(\Magento\Config\Model\Config\Structure\Data::class),
            $this->getConfig()
        );

        $sections = $this->getArray($config, 'config', 'system', 'sections');

        $this->assertSame(['tab' => 'catalog'], $sections[ 'catalog' ] ?? null);

        $groups = $this->getArray($sections, 'task_import', 'children');
        $settings = $this->getArray($groups, 'settings', 'children');

        $this->assertSame(
            [
                'path',
                'overwrite_task_general',
                'max_memory',
                'depends_on',
                'wait_for_predecessor',
                'suppress_empty_mails'
            ],
            array_keys($settings)
        );
        $this->assertArrayNotHasKey('depends', $this->getArray($settings, 'path'));
        $this->assertArrayNotHasKey('depends', $this->getArray($settings, 'depends_on'));
        $this->assertSame(
            ['overwrite_task_general', 'send'],
            array_keys($this->getArray($groups, 'summary_error', 'children', 'sender', 'depends', 'fields'))
        );
    }

    /**
     * Tests that the general section can not be overwritten.
     *
     * @return void
     */
    public function testGeneralSection(): void
    {
        $section = (new Data())->getTaskSection('task_general', true);

        $this->assertArrayNotHasKey(
            'overwrite_task_general',
            $this->getArray($section, 'children', 'logging', 'children')
        );
        $this->assertArrayNotHasKey('depends_on', $this->getArray($section, 'children', 'settings', 'children'));
    }
}
