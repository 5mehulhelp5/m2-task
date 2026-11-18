<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Plugin\Config\Model\Config\Structure;

use Infrangible\Task\Model\Config\Source\LogLevel;
use Magento\Config\Model\Config\Source\Email\Identity;
use Magento\Config\Model\Config\Source\Yesno;

/**
 * Adds the groups that every task has to the configuration sections in the tab "Tasks": settings, logging and the
 * success and error summary. The section task_general holds the general values; the section of a task can overwrite
 * each group and adds the setting "Depends On".
 */
class Data
{
    /** Fields of the summaries. */
    private const SUMMARY_FIELDS = [
        'send'                  => ['type' => 'select', 'label' => 'Send', 'source' => Yesno::class],
        'sender'                => [
            'type'    => 'select',
            'label'   => 'Sender',
            'source'  => Identity::class,
            'depends' => ['send' => '1']
        ],
        'recipients'            => [
            'label'   => 'Recipients',
            'comment' => 'Divided by semicolon',
            'depends' => ['send' => '1']
        ],
        'copy_recipients'       => [
            'label'   => 'Copy Recipients',
            'comment' => 'Divided by semicolon',
            'depends' => ['send' => '1']
        ],
        'blind_copy_recipients' => [
            'label'   => 'Blind Copy Recipients',
            'comment' => 'Divided by semicolon',
            'depends' => ['send' => '1']
        ],
        'subject'               => ['label' => 'Subject', 'depends' => ['send' => '1']]
    ];

    /**
     * Adds the groups of tasks to the sections in the tab "Tasks", and sorts the fields of their groups.
     *
     * @param \Magento\Config\Model\Config\Structure\Data $object
     * @param array $config
     * @phpstan-param array<int|string, mixed> $config
     *
     * @return array
     * @phpstan-return array{0: array<int|string, mixed>}
     * @noinspection PhpUnusedParameterInspection
     */
    public function beforeMerge(\Magento\Config\Model\Config\Structure\Data $object, array $config): array
    {
        $root = $config[ 'config' ] ?? null;
        $system = is_array($root) ? ($root[ 'system' ] ?? null) : null;
        $sections = is_array($system) ? ($system[ 'sections' ] ?? null) : null;

        if (! is_array($root) || ! is_array($system) || ! is_array($sections)) {
            return [$config];
        }

        foreach ($sections as $sectionName => $sectionData) {
            if (! is_array($sectionData) || ($sectionData[ 'tab' ] ?? null) !== 'tasks') {
                continue;
            }

            $sectionName = (string)$sectionName;
            $sectionData = array_replace_recursive(
                $sectionData,
                $this->getTaskSection($sectionName, $sectionName === 'task_general')
            );

            $groups = is_array($sectionData[ 'children' ] ?? null) ? $sectionData[ 'children' ] : [];

            foreach ($groups as $groupName => $groupData) {
                if (is_array($groupData) && is_array($groupData[ 'children' ] ?? null)) {
                    $fields = $groupData[ 'children' ];

                    uasort($fields, fn(mixed $field1, mixed $field2): int => $this->getSortOrder($field1) <=>
                        $this->getSortOrder($field2));

                    $groups[ $groupName ][ 'children' ] = $fields;
                }
            }

            $sectionData[ 'children' ] = $groups;

            $sections[ $sectionName ] = $sectionData;
        }

        $system[ 'sections' ] = $sections;
        $root[ 'system' ] = $system;
        $config[ 'config' ] = $root;

        return [$config];
    }

    /**
     * Returns the groups of a task section.
     *
     * @param string $taskName
     * @param bool $isGeneralTask
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    public function getTaskSection(string $taskName, bool $isGeneralTask): array
    {
        $generalSettings = ['max_memory', 'wait_for_predecessor', 'suppress_empty_mails'];

        $groupsData = [
            'settings'        => [
                'label'  => 'Settings',
                'fields' => [
                    'max_memory'           => ['label' => 'Max Memory', 'comment' => 'In MB', 'sortOrder' => 10],
                    'wait_for_predecessor' => [
                        'type'      => 'select',
                        'label'     => 'Wait for Predecessor',
                        'source'    => Yesno::class,
                        'sortOrder' => 21
                    ],
                    'suppress_empty_mails' => [
                        'type'      => 'select',
                        'label'     => 'Suppress Empty Mails',
                        'source'    => Yesno::class,
                        'sortOrder' => 30
                    ]
                ]
            ],
            'logging'         => [
                'label'  => 'Logging',
                'fields' => [
                    'log_level'         => ['type' => 'select', 'label' => 'Log Level', 'source' => LogLevel::class],
                    'log_warn_as_error' => [
                        'type'   => 'select',
                        'label'  => 'Log Warning as Error',
                        'source' => Yesno::class
                    ]
                ]
            ],
            'summary_success' => ['label' => 'Success Summary', 'fields' => self::SUMMARY_FIELDS],
            'summary_error'   => ['label' => 'Error Summary', 'fields' => self::SUMMARY_FIELDS]
        ];

        if (! $isGeneralTask) {
            $groupsData[ 'settings' ][ 'fields' ][ 'depends_on' ] = [
                'label'     => 'Depends On',
                'comment'   => 'Other tasks divided by semicolon',
                'sortOrder' => 20
            ];

            foreach ($groupsData as $groupName => $groupData) {
                foreach (array_keys($groupData[ 'fields' ]) as $fieldName) {
                    if ($groupName !== 'settings' || in_array($fieldName, $generalSettings, true)) {
                        $depends = $groupData[ 'fields' ][ $fieldName ][ 'depends' ] ?? [];

                        $groupsData[ $groupName ][ 'fields' ][ $fieldName ][ 'depends' ] =
                            ['overwrite_task_general' => '1'] + $depends;
                    }
                }

                $groupsData[ $groupName ][ 'fields' ][ 'overwrite_task_general' ] = [
                    'type'      => 'select',
                    'label'     => 'Overwrite Task General',
                    'source'    => Yesno::class,
                    'sortOrder' => 5
                ];
            }
        }

        $groups = [];

        foreach ($groupsData as $groupName => $groupData) {
            $groups[ $groupName ] = $this->getTaskSectionGroup(
                $taskName,
                $groupName,
                $groupData[ 'label' ],
                count($groups) + 10,
                $groupData[ 'fields' ]
            );
        }

        return [
            'resource'      => 'Infrangible_Task::config_infrangible_task',
            'translate'     => 'label',
            'showInDefault' => 1,
            'showInWebsite' => 1,
            'showInStore'   => 1,
            'children'      => $groups
        ];
    }

    /**
     * Returns a group of a task section.
     *
     * @param string $taskName
     * @param string $groupName
     * @param string $label
     * @param int $sortOrder
     * @param array $fieldsData
     * @phpstan-param array<string, array<string, mixed>> $fieldsData
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    public function getTaskSectionGroup(
        string $taskName,
        string $groupName,
        string $label,
        int $sortOrder,
        array $fieldsData
    ): array {
        $fields = [];

        foreach ($fieldsData as $fieldName => $fieldData) {
            $fields[ $fieldName ] = $this->getTaskSectionField(
                $taskName,
                $groupName,
                $fieldName,
                $this->stringValue($fieldData[ 'type' ] ?? 'text'),
                $this->stringValue($fieldData[ 'label' ] ?? ''),
                $this->stringValue($fieldData[ 'comment' ] ?? ''),
                $this->stringValue($fieldData[ 'source' ] ?? ''),
                is_array($fieldData[ 'depends' ] ?? null) ? $fieldData[ 'depends' ] : [],
                is_int($fieldData[ 'sortOrder' ] ?? null) ? $fieldData[ 'sortOrder' ] : count($fields) + 10
            );
        }

        return [
            '_elementType'  => 'group',
            'type'          => 'text',
            'id'            => $groupName,
            'path'          => $taskName,
            'translate'     => 'label',
            'label'         => $label,
            'sortOrder'     => $sortOrder,
            'showInDefault' => 1,
            'showInWebsite' => 1,
            'showInStore'   => 1,
            'children'      => $fields
        ];
    }

    /**
     * Returns a field of a task section.
     *
     * @param string $taskName
     * @param string $groupName
     * @param string $fieldName
     * @param string $type
     * @param string $label
     * @param string $comment
     * @param string $sourceClass
     * @param array $depends
     * @param int $sortOrder
     * @phpstan-param array<int|string, mixed> $depends
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    public function getTaskSectionField(
        string $taskName,
        string $groupName,
        string $fieldName,
        string $type,
        string $label,
        string $comment,
        string $sourceClass,
        array $depends,
        int $sortOrder
    ): array {
        $fieldData = [
            '_elementType'  => 'field',
            'id'            => $fieldName,
            'path'          => sprintf('%s/%s', $taskName, $groupName),
            'type'          => $type,
            'translate'     => 'label comment',
            'label'         => $label,
            'sortOrder'     => $sortOrder,
            'showInDefault' => 1,
            'showInWebsite' => 1,
            'showInStore'   => 1
        ];

        if ($comment !== '') {
            $fieldData[ 'comment' ] = $comment;
        }

        if ($sourceClass !== '') {
            $fieldData[ 'source_model' ] = $sourceClass;
        }

        foreach ($depends as $dependFieldName => $dependFieldValue) {
            $fieldData[ 'depends' ][ 'fields' ][ $dependFieldName ] = [
                '_elementType' => 'field',
                'id'           => sprintf('%s/%s/%s', $taskName, $groupName, $dependFieldName),
                'value'        => $dependFieldValue,
                'dependPath'   => [$taskName, $groupName, $dependFieldName]
            ];
        }

        return $fieldData;
    }

    /**
     * Returns the sort order of a field.
     *
     * @param mixed $field
     *
     * @return int
     */
    private function getSortOrder(mixed $field): int
    {
        $sortOrder = is_array($field) ? ($field[ 'sortOrder' ] ?? 0) : 0;

        return is_numeric($sortOrder) ? (int)$sortOrder : 0;
    }

    /**
     * Returns a value as string.
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
