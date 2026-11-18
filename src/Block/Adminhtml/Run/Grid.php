<?php /** @noinspection PhpDeprecationInspection The list of runs is a legacy grid of Magento. */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Block\Adminhtml\Run;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Helper\Grid as GridHelper;
use Infrangible\BackendWidget\Helper\Session;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Registry;
use Infrangible\Task\Model\Config\Source\TaskName;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Helper\Data;
use Magento\Eav\Model\Config;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Validator\UniversalFactory;

/**
 * The list of runs with their status and duration.
 */
class Grid extends \Infrangible\BackendWidget\Block\Grid
{
    /** Status of a run that has not finished. */
    public const STATUS_RUNNING = 1;

    /** Status of a run that finished. */
    public const STATUS_FINISHED = 2;

    /** Status of a run whose process ended without finishing it (see Infrangible\Task\Cron\Check). */
    public const STATUS_BROKEN = 3;

    /** CSS classes of the status. */
    private const STATUS_CLASSES = [
        self::STATUS_RUNNING  => 'task-run-status-running',
        self::STATUS_FINISHED => 'task-run-status-finished',
        self::STATUS_BROKEN   => 'task-run-status-broken'
    ];

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param Database $databaseHelper
     * @param Arrays $arrays
     * @param Variables $variables
     * @param Registry $registryHelper
     * @param GridHelper $gridHelper
     * @param Session $sessionHelper
     * @param UniversalFactory $universalFactory
     * @param Config $eavConfig
     * @param TaskName $taskName
     * @param array $data
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Database $databaseHelper,
        Arrays $arrays,
        Variables $variables,
        Registry $registryHelper,
        GridHelper $gridHelper,
        Session $sessionHelper,
        UniversalFactory $universalFactory,
        Config $eavConfig,
        protected TaskName $taskName,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $backendHelper,
            $databaseHelper,
            $arrays,
            $variables,
            $registryHelper,
            $gridHelper,
            $sessionHelper,
            $universalFactory,
            $eavConfig,
            $data
        );
    }

    /**
     * Sorts the newest runs first.
     *
     * @return void
     * @throws FileSystemException
     */
    public function _construct(): void
    {
        parent::_construct();

        $this->setDefaultSort('start_at');
        $this->setDefaultDir('DESC');
    }

    /**
     * Adds the status and the duration in seconds.
     *
     * @param AbstractDb $collection
     *
     * @return void
     */
    protected function prepareCollection(AbstractDb $collection): void
    {
        if ($collection instanceof AbstractCollection) {
            $collection->addExpressionFieldToSelect(
                'status',
                sprintf(
                    'IF({{finish_at}} IS NULL, %d, IF({{max_memory_usage}} IS NULL, %d, %d))',
                    self::STATUS_RUNNING,
                    self::STATUS_BROKEN,
                    self::STATUS_FINISHED
                ),
                ['finish_at' => 'finish_at', 'max_memory_usage' => 'max_memory_usage']
            );
            $collection->addExpressionFieldToSelect(
                'duration',
                'TIMESTAMPDIFF(SECOND, {{start_at}}, {{finish_at}})',
                ['start_at' => 'start_at', 'finish_at' => 'finish_at']
            );
        }
    }

    /**
     * Adds the columns.
     *
     * @return void
     * @throws Exception
     */
    protected function prepareFields(): void
    {
        $this->addTextColumn('store_code', __('Store Code')->render());
        $this->addOptionsColumn('task_name', __('Task Name')->render(), $this->taskName->toArray());
        $this->addTextColumn('task_id', __('Task Id')->render());
        $this->addTextColumn('process_id', __('Process Id')->render());
        $this->addOptionsColumnWithFilterConditionAndFrame(
            'status',
            __('Status')->render(),
            [
                self::STATUS_RUNNING  => __('Running'),
                self::STATUS_FINISHED => __('Finished'),
                self::STATUS_BROKEN   => __('Broken')
            ],
            [$this, 'filterCalculatedField'],
            [$this, 'decorateStatus']
        );
        $this->addYesNoColumn('success', __('Success')->render());
        $this->addYesNoColumn('empty_run', __('Empty Run')->render());
        $this->addYesNoColumn('test', __('Test')->render());
        $this->addDatetimeColumn('start_at', __('Start Date')->render());
        $this->addDatetimeColumn('finish_at', __('Finish Date')->render());
        $this->addNumberColumnWithFilterCondition(
            'duration',
            __('Duration')->render(),
            [$this, 'filterCalculatedField']
        );
        $this->addNumberColumn('max_memory_usage', __('Memory')->render());
    }

    /**
     * Returns the columns that are hidden by default.
     *
     * @return string[]
     */
    protected function getHiddenFieldNames(): array
    {
        return ['process_id', 'empty_run', 'test', 'finish_at', 'duration', 'max_memory_usage'];
    }

    /**
     * Filters a calculated field (status, duration), which is only known after grouping, with HAVING.
     *
     * @param AbstractCollection $collection
     * @param Column $column
     *
     * @return void
     */
    public function filterCalculatedField(
        AbstractCollection $collection,
        Column $column
    ): void {
        $field = $column->getData('filter_index') ?: $column->getData('index');
        $filter = $column->getFilter();

        if ($filter !== false && is_string($field) && $field !== '') {
            $collection->getSelect()->having(
                $collection->getConnection()->prepareSqlCondition($field, $filter->getCondition())
            );
        }
    }

    /**
     * Shows the status with the color of its class.
     *
     * @param string $value
     * @param DataObject $row
     *
     * @return string
     */
    public function decorateStatus(string $value, DataObject $row): string
    {
        $status = $row->getData('status');
        $class = is_numeric($status) ? (self::STATUS_CLASSES[ (int)$status ] ?? '') : '';

        return sprintf('<span class="%s"><span>%s</span></span>', $class, $this->_escaper->escapeHtml($value));
    }
}
