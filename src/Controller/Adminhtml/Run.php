<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Controller\Adminhtml;

use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Stores;
use Infrangible\Task\Helper\Data;
use Infrangible\Task\Helper\Values;
use Infrangible\Task\Model\Session;
use Infrangible\Task\Task\Base;
use LogicException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\TranslateInterface;
use Magento\Framework\Validator\Locale as LocaleValidator;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\App\Emulation;
use Throwable;

/**
 * Base of the admin controllers that run a task: runs the task with the store, the locale and the test mode of the
 * request, and shows the result, redirects with a message, or shows the error.
 *
 * Only admin users with the resource Infrangible_Task::infrangible_task and the resource of the task may run it.
 */
abstract class Run extends Action
{
    /** @var Base|null */
    private ?Base $task = null;

    /**
     * @param Context $context
     * @param Stores $storeHelper
     * @param Instances $instanceHelper
     * @param Data $taskHelper
     * @param Session $taskSession
     * @param AuthSession $authSession
     * @param Emulation $appEmulation
     * @param ResolverInterface $localeResolver
     * @param TranslateInterface $translate
     * @param LocaleValidator $localeValidator
     * @param Values $values
     */
    public function __construct(
        Context $context,
        protected Stores $storeHelper,
        protected Instances $instanceHelper,
        protected Data $taskHelper,
        protected Session $taskSession,
        protected AuthSession $authSession,
        protected Emulation $appEmulation,
        protected ResolverInterface $localeResolver,
        protected TranslateInterface $translate,
        protected LocaleValidator $localeValidator,
        protected Values $values
    ) {
        parent::__construct($context);
    }

    /**
     * Returns the name of the task, which is also the name of its configuration section (task_<name>).
     *
     * @return string
     */
    abstract protected function getTaskName(): string;

    /**
     * Returns the class of the task, which extends Infrangible\Task\Task\Base.
     *
     * @return string
     */
    abstract protected function getClassName(): string;

    /**
     * Returns the ACL resource that allows running the task.
     *
     * @return string
     */
    abstract protected function getTaskResourceId(): string;

    /**
     * Allows running the task with the resource of the tasks and the resource of the task.
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Infrangible_Task::infrangible_task') &&
            $this->_authorization->isAllowed($this->getTaskResourceId());
    }

    /**
     * Returns the task.
     *
     * @return Base
     */
    public function getTask(): Base
    {
        if ($this->task === null) {
            $task = $this->instanceHelper->getInstance($this->getClassName());

            if (! $task instanceof Base) {
                throw new LogicException(sprintf('Task must extend %s', Base::class));
            }

            $this->task = $task;
        }

        return $this->task;
    }

    /**
     * Runs the task and shows the result or the error.
     *
     * @return ResultInterface|ResponseInterface
     * @throws void
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $taskName = $this->getTaskName();

        $this->taskSession->setData('task_name', $taskName);
        $this->taskSession->setData('task_resource', $this->getTaskResourceId());

        if ($taskName === '') {
            return $this->redirectToError(__('Please specify a task name!')->render());
        }

        $storeCode = $this->values->string($this->getRequest()->getParam('store_code'));
        $storeCode = $storeCode === '' ? 'admin' : $storeCode;

        try {
            $storeId = $this->values->int($this->storeHelper->getStore($storeCode)->getId());
        } catch (Throwable $exception) {
            return $this->redirectToError($exception->getMessage());
        }

        try {
            [$task, $taskResult] = $this->launchTask($storeId, $storeCode, $taskName);
        } catch (Throwable $exception) {
            return $this->redirectToError($exception->getMessage());
        }

        $redirectPath = $this->getRedirectPath();

        if ($redirectPath !== null) {
            if ($this->isAddResultMessage()) {
                $this->addResultMessage($taskName, $taskResult, $task->getRun()->getId());
            }

            return $this->resultRedirectFactory->create()->setPath($redirectPath);
        }

        return $this->showResult($task, $taskName);
    }

    /**
     * Runs the task with the store in an emulated environment.
     *
     * @param int $storeId
     * @param string $storeCode
     * @param string $taskName
     *
     * @return array{0: Base, 1: bool} the task and its result
     * @throws LocalizedException
     * @throws FileSystemException
     */
    private function launchTask(int $storeId, string $storeCode, string $taskName): array
    {
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_ADMINHTML);

        try {
            $this->setLocale();

            $task = $this->getTask();

            $taskResult = $task->launchFromAdmin(
                $storeCode,
                $taskName,
                filter_var($this->getRequest()->getParam('test', false), FILTER_VALIDATE_BOOLEAN)
            );

            return [$task, $taskResult];
        } finally {
            $this->appEmulation->stopEnvironmentEmulation();
        }
    }

    /**
     * Sets the locale of the request, or of the admin user (parameter backend_user), for the texts of the task.
     *
     * @return void
     */
    private function setLocale(): void
    {
        $locale = null;

        if ($this->getRequest()->getParam('backend_user')) {
            $locale = $this->authSession->getUser()?->getInterfaceLocale();
        }

        $requestLocale = $this->values->string($this->getRequest()->getParam('locale'));

        if ($requestLocale !== '' && $this->localeValidator->isValid($requestLocale)) {
            $locale = $requestLocale;
        }

        if (is_string($locale) && $locale !== '') {
            $this->localeResolver->setLocale($locale);
            $this->translate->setLocale($locale);
            $this->translate->loadData(Area::AREA_ADMINHTML);
        }
    }

    /**
     * Shows the summary of the task.
     *
     * @param Base $task
     * @param string $taskName
     *
     * @return ResultInterface|ResponseInterface
     */
    private function showResult(Base $task, string $taskName): ResultInterface|ResponseInterface
    {
        // @phpstan-ignore argument.type (Magento also accepts a list of layout handles)
        $this->_view->loadLayout(['default', 'infrangible_task_run_result']);

        $resultBlock = $this->_view->getLayout()->getBlock('task_result');

        if (! $resultBlock instanceof Template) {
            return $this->redirectToError('Result block not found in layout');
        }

        $taskTitle = $this->values->string(
            $this->taskHelper->getTaskConfigValue($taskName, 'data', 'title', $taskName, false, true)
        );

        // phpcs:ignore Magento2.Translation.ConstantUsage -- the titles of the tasks are translated by their modules
        $resultBlock->setData('title', __($taskTitle));
        $resultBlock->setData('result', $task->getSummaryRecords(Base::SUMMARY_TYPE_ALL, true));

        return $this->_view->getPage();
    }

    /**
     * Redirects to the page that shows the reason why the task could not run.
     *
     * @param string $reason
     *
     * @return ResultInterface
     */
    private function redirectToError(string $reason): ResultInterface
    {
        $this->taskSession->setData('task_error_reason', $reason);

        return $this->resultRedirectFactory->create()->setPath('infrangible_task/run/error');
    }

    /**
     * Returns the route to redirect to after the task ran, or null to show the result.
     *
     * @return string|null
     */
    protected function getRedirectPath(): ?string
    {
        return null;
    }

    /**
     * Returns whether a message with a link to the log of the run is shown after the redirect.
     *
     * @return bool
     */
    protected function isAddResultMessage(): bool
    {
        return false;
    }

    /**
     * Adds the message with a link to the log of the run, which leads back to the redirect path.
     *
     * @param string $taskName
     * @param bool $taskResult
     * @param mixed $runId
     *
     * @return void
     */
    protected function addResultMessage(string $taskName, bool $taskResult, mixed $runId): void
    {
        $messageData = [
            'task_name' => $taskName,
            'run_url'   => $this->_url->getUrl(
                'infrangible_task/run_result/view',
                [
                    'run_id'     => $runId,
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction -- the route is a parameter of the URL
                    'back_route' => base64_encode((string)$this->getRedirectPath())
                ]
            )
        ];

        if ($taskResult) {
            $this->messageManager->addComplexSuccessMessage('taskSuccessMessage', $messageData);
        } else {
            $this->messageManager->addComplexErrorMessage('taskErrorMessage', $messageData);
        }
    }
}
