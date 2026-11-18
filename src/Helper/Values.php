<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Helper;

/**
 * Converts values of the configuration, the registry, the session and requests.
 */
class Values
{
    /**
     * Returns a scalar value as string, other values (null, arrays, objects) as an empty string.
     *
     * Unlike FeWeDev\Base\Variables::stringValue(), which returns "NULL" for null.
     *
     * @param mixed $value
     *
     * @return string
     */
    public function string(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }

    /**
     * Returns a numeric value as integer, other values as 0, for example the IDs of Magento, which are strings.
     *
     * @param mixed $value
     *
     * @return int
     */
    public function int(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }
}
