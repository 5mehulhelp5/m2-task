<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Controller\Adminhtml\Run\Result;

use Infrangible\Task\Traits\Run;

/**
 * The list of runs.
 */
class Index extends \Infrangible\BackendWidget\Controller\Backend\Object\Index
{
    use Run;
}
