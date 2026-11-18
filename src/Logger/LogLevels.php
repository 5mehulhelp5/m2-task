<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Logger;

use Psr\Log\LogLevel;

/**
 * Converts the PSR-3 log levels into severities that can be compared, with the values of Monolog.
 */
class LogLevels
{
    /** The log level of the configuration that logs nothing. */
    public const OFF = 'off';

    /** Severities of the log levels. */
    private const SEVERITIES = [
        LogLevel::DEBUG     => 100,
        LogLevel::INFO      => 200,
        LogLevel::NOTICE    => 250,
        LogLevel::WARNING   => 300,
        LogLevel::ERROR     => 400,
        LogLevel::CRITICAL  => 500,
        LogLevel::ALERT     => 550,
        LogLevel::EMERGENCY => 600
    ];

    /**
     * Returns the name of a log level in lower case; unknown levels are "info".
     *
     * @param mixed $level
     *
     * @return string
     */
    public function getName(mixed $level): string
    {
        $name = is_string($level) ? strtolower($level) : '';

        return array_key_exists($name, self::SEVERITIES) ? $name : LogLevel::INFO;
    }

    /**
     * Returns the severity of a log level.
     *
     * @param mixed $level
     *
     * @return int
     */
    public function getSeverity(mixed $level): int
    {
        return self::SEVERITIES[ $this->getName($level) ];
    }

    /**
     * Returns the lowest severity that is logged with the configured log level; "off" logs nothing.
     *
     * @param string $configuredLevel
     *
     * @return int
     */
    public function getMinimumSeverity(string $configuredLevel): int
    {
        return strtolower($configuredLevel) === self::OFF ? PHP_INT_MAX : $this->getSeverity($configuredLevel);
    }

    /**
     * Returns the lowest severity that counts as error: warnings, if they are logged as errors.
     *
     * @param bool $warningAsError
     *
     * @return int
     */
    public function getErrorSeverity(bool $warningAsError): int
    {
        return self::SEVERITIES[ $warningAsError ? LogLevel::WARNING : LogLevel::ERROR ];
    }
}
