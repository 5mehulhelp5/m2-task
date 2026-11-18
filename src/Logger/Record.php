<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Logger;

/**
 * A record of a summary: the PSR-3 log level and the message.
 */
class Record
{
    /**
     * @param string $level
     * @param string $message
     */
    public function __construct(private string $level, private string $message)
    {
    }

    /**
     * Returns the PSR-3 log level.
     *
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * Sets the PSR-3 log level.
     *
     * @param string $level
     *
     * @return void
     */
    public function setLevel(string $level): void
    {
        $this->level = $level;
    }

    /**
     * Returns the message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Sets the message.
     *
     * @param string $message
     *
     * @return void
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
