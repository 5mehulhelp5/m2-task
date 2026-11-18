<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Controller\Adminhtml\Run\Result;

use Infrangible\BackendWidget\Controller\Backend\Object\Edit;
use Infrangible\Task\Block\Adminhtml\Run\View as RunView;
use Infrangible\Task\Traits\Run;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Shows the log files of a run.
 */
class View extends Edit
{
    use Run;

    /**
     * Returns the message if the run was not found.
     *
     * @return string
     */
    protected function getObjectNotFoundMessage(): string
    {
        return __('Could not find run!')->render();
    }

    /**
     * Shows the log files of the run, or redirects to the list of runs, if the run was not found.
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $run = $this->initEditableObject();

        if ($run === null) {
            return $this->getResponse();
        }

        $this->initAction();

        $blockData = ['run_id' => $run->getId()];

        $backRoute = $this->getRequest()->getParam('back_route');

        if (is_string($backRoute) && $backRoute !== '') {
            $blockData[ 'back_route' ] = $backRoute;
        }

        $block = $this->_view->getLayout()->createBlock(RunView::class, '', ['data' => $blockData]);

        if ($block instanceof RunView) {
            $this->_addContent($block);
        }

        $this->finishAction(__('View Log')->render());

        return $this->_view->getPage();
    }
}
