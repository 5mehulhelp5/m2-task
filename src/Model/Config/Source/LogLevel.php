<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Model\Config\Source;

use Infrangible\Task\Logger\LogLevels;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;
use Psr\Log\LogLevel as PsrLogLevel;

/**
 * The log levels of tasks.
 */
class LogLevel implements OptionSourceInterface
{
    /**
     * Returns the log levels as options.
     *
     * @return array
     * @phpstan-return array<int, array{value: string, label: Phrase}>
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->toArray() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    /**
     * Returns the log levels as value => label.
     *
     * @return array
     * @phpstan-return array<string, Phrase>
     */
    public function toArray(): array
    {
        return [
            LogLevels::OFF         => __('Off: no messages'),
            PsrLogLevel::EMERGENCY => __('Fatal: emergency messages'),
            PsrLogLevel::ALERT     => __('Fatal: alert messages'),
            PsrLogLevel::CRITICAL  => __('Error: critical messages'),
            PsrLogLevel::ERROR     => __('Error: error messages'),
            PsrLogLevel::WARNING   => __('Warning: warning messages'),
            PsrLogLevel::NOTICE    => __('Info: normal but significant messages'),
            PsrLogLevel::INFO      => __('Info: informational messages'),
            PsrLogLevel::DEBUG     => __('Debug: debug messages')
        ];
    }
}
