<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Traits;

/**
 * The runs as objects of the list and view controllers of m2-backend-widget: they can only be viewed.
 */
trait Run
{
    /**
     * Returns the key of the module.
     *
     * @return string
     */
    protected function getModuleKey(): string
    {
        return 'Infrangible_Task';
    }

    /**
     * Returns the resource key.
     *
     * @return string
     */
    protected function getResourceKey(): string
    {
        return 'task_run';
    }

    /**
     * Returns the menu key.
     *
     * @return string
     */
    protected function getMenuKey(): string
    {
        return 'task_run';
    }

    /**
     * Returns the object name.
     *
     * @return string
     */
    protected function getObjectName(): string
    {
        return 'Run';
    }

    /**
     * Returns the object field.
     *
     * @return string|null
     */
    protected function getObjectField(): ?string
    {
        return 'run_id';
    }

    /**
     * Returns the title.
     *
     * @return string
     */
    protected function getTitle(): string
    {
        return __('Tasks')->render();
    }

    /**
     * Allows the add.
     *
     * @return bool
     */
    protected function allowAdd(): bool
    {
        return false;
    }

    /**
     * Allows the edit.
     *
     * @return bool
     */
    protected function allowEdit(): bool
    {
        return false;
    }

    /**
     * Allows the view.
     *
     * @return bool
     */
    protected function allowView(): bool
    {
        return true;
    }

    /**
     * Allows the delete.
     *
     * @return bool
     */
    protected function allowDelete(): bool
    {
        return false;
    }

    /**
     * Returns the edit url route.
     *
     * @return string
     */
    protected function getEditUrlRoute(): string
    {
        return '*/*/view';
    }
}
