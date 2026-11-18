<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Controller\Adminhtml\Run;

use Infrangible\Task\Helper\Values;
use Infrangible\Task\Model\Session;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Element\Template;

/**
 * Shows why a task could not run, once after the redirect of the controller that tried to run it.
 */
class Error extends Action
{
    /**
     * @param Context $context
     * @param Session $taskSession
     * @param Values $values
     */
    public function __construct(Context $context, protected Session $taskSession, protected Values $values)
    {
        parent::__construct($context);
    }

    /**
     * Allows the page to users who may run the task that failed.
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        $taskResource = $this->values->string($this->taskSession->getData('task_resource'));

        return $this->_authorization->isAllowed('Infrangible_Task::infrangible_task') &&
            ($taskResource === '' || $this->_authorization->isAllowed($taskResource));
    }

    /**
     * Shows the reason, or redirects to the dashboard, if there is none.
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $reason = $this->values->string($this->taskSession->getData('task_error_reason'));

        if ($reason === '') {
            return $this->resultRedirectFactory->create()->setPath('admin/dashboard');
        }

        // @phpstan-ignore argument.type (Magento also accepts a list of layout handles)
        $this->_view->loadLayout(['default', 'infrangible_task_run_error']);

        $errorBlock = $this->_view->getLayout()->getBlock('task_error');

        if ($errorBlock instanceof Template) {
            $errorBlock->setData('reason', $reason);
        }

        $this->taskSession->unsetData('task_error_reason');

        return $this->_view->getPage();
    }
}
