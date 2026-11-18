<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Model;

use Infrangible\Task\Model\Session\Storage;
use Magento\Framework\Session\SessionManager;

/**
 * Session of the admin controllers of tasks, which keeps the reason why a task could not run for the error page.
 */
class Session extends SessionManager
{
    /**
     * Sets a value.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function setData(string $key, mixed $value = null): void
    {
        $storage = $this->storage;

        if ($storage instanceof Storage) {
            $storage->setData($key, $value);
        }
    }

    /**
     * Removes a value.
     *
     * @param string|null $key
     *
     * @return void
     */
    public function unsetData(?string $key = null): void
    {
        $storage = $this->storage;

        if ($storage instanceof Storage) {
            $storage->unsetData($key);
        }
    }
}
