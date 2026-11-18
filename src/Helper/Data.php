<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Helper;

use FeWeDev\Base\Variables;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Reads the configuration of tasks: section task_<task name>, or the general section task_general for the groups that
 * a task can overwrite (settings, logging, summary_success, summary_error).
 */
class Data
{
    /** Groups of the general section, which a task uses unless it overwrites them. */
    private const GENERAL_GROUPS = ['settings', 'logging', 'summary_success', 'summary_error'];

    /** Fields of the group settings in the general section; the other settings belong to the task only. */
    private const GENERAL_SETTINGS = ['max_memory', 'wait_for_predecessor', 'suppress_empty_mails'];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Variables $variables
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected StoreManagerInterface $storeManager,
        protected Variables $variables
    ) {
    }

    /**
     * Returns a configuration value of a task with the current store.
     *
     * The value of the task is used for its own groups and fields, for groups it overwrites (field
     * overwrite_task_general), and if forced; otherwise, or if the task has no value, the value of task_general, and
     * then the default value.
     *
     * @param string $taskName
     * @param string $section
     * @param string $field
     * @param mixed $defaultValue
     * @param bool $isFlag
     * @param bool $forceTaskConfigValue
     *
     * @return mixed
     */
    public function getTaskConfigValue(
        string $taskName,
        string $section,
        string $field,
        mixed $defaultValue = null,
        bool $isFlag = false,
        bool $forceTaskConfigValue = false
    ): mixed {
        $value = null;

        if ($taskName !== '' && ($forceTaskConfigValue || ! $this->isGeneralField($section, $field) ||
                $this->isSetFlag(sprintf('task_%s/%s/overwrite_task_general', $taskName, $section)))) {
            $value = $this->getValue(sprintf('task_%s/%s/%s', $taskName, $section, $field), $isFlag);
        }

        $value ??= $this->getValue(sprintf('task_general/%s/%s', $section, $field), $isFlag);

        return $value ?? $defaultValue;
    }

    /**
     * Returns a configuration value of the current store.
     *
     * @param string $path
     *
     * @return mixed
     */
    public function getStoreConfig(string $path): mixed
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * Checks whether a field has a general value in task_general, which a task uses unless it overwrites the group.
     *
     * @param string $section
     * @param string $field
     *
     * @return bool
     */
    private function isGeneralField(string $section, string $field): bool
    {
        return in_array($section, self::GENERAL_GROUPS, true) &&
            ($section !== 'settings' || in_array($field, self::GENERAL_SETTINGS, true));
    }

    /**
     * Returns a configuration value, as flag if requested; null if it is not set.
     *
     * @param string $path
     * @param bool $isFlag
     *
     * @return mixed
     */
    private function getValue(string $path, bool $isFlag): mixed
    {
        $value = $this->getStoreConfig($path);

        return $isFlag && $value !== null ? $this->isSetFlag($path) : $value;
    }

    /**
     * Returns a flag of the current store.
     *
     * @param string $path
     *
     * @return bool
     */
    private function isSetFlag(string $path): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * Returns the ID of the current store, or null (the default scope) if there is none.
     *
     * @return int|null
     */
    private function getStoreId(): ?int
    {
        try {
            // the ID is a string at runtime, although it is declared as integer
            return $this->variables->intValue($this->storeManager->getStore()->getId());
        } catch (NoSuchEntityException) {
            return null;
        }
    }
}
