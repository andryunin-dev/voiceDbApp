<?php
/**
 * Created by PhpStorm.
 * User: Dmitry
 * Date: 09.11.2017
 * Time: 11:21
 */

namespace App\Components\Tables;


use App\Components\Sql\SqlFilter;
use T4\Core\Config;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Orm\Model;

class PivotTableConfig extends Config
    implements PivotTableConfigInterface
{
    const BASE_CONF_PATH = ROOT_PATH . DS . 'Configs' . DS;

    protected $columnPropertiesTemplate = [
        'id' => '',
        'title' => '',
        'width' => 0,
        'sortable' => false,
        'filterable' => false
    ];
    /**
     * @var array
     *
     * 'preFilter' - preFilter for pivot column values
     * 'sortBy' - sort columns and directions for pivot column ['column_1' => 'asc|desc', 'column_N' => 'asc|desc']
     * 'width' - width for each column from pivot columns set.
     *      If set in 'px' (ie '20px'), pivot column's width from columns properties will be ignored.
     *      If set in percents (ie 20), width for each column will be calculated by width from columns properties
     */
    protected $pivotColumnPropertiesTemplate = [
        'preFilter' => [], //preFilter for pivot column values
        'sortBy' => [], //sort columns and directions for pivot column ['column_1' => 'asc|desc', 'column_N' => 'asc|desc']
        'width' => 0, //width for each column from pivot values.
    ];

    public function __construct(string $tableName, string $class = null)
    {
        parent::__construct();
        if (empty($tableName)) {
            throw new Exception('Table name can not be empty');
        }
        $path = self::BASE_CONF_PATH . $tableName;
        /* if class is not set try to load existing config */
        if (empty($class)) {
            $this->load($path);
        } elseif (! class_exists($class)) {
            throw new Exception('Class ' . $class . ' is not exists');
        } elseif (get_parent_class($class) != Model::class) {
            throw new Exception('Class for table must extends Model class');
        } else {
            $this->setPath($path);
            $this->className = $class;
        }
    }

    /**
     * @param array $columns only columns names
     * All columns names have to belong a class that specified in construct method
     * @return mixed if $columns is null - return columns array for current table
     *
     * if $columns is null - return columns array for current table
     * if $columns is array - set columns from this array for current table
     * this method should be called first
     * @throws Exception
     */
    public function columns(array $columns = null)
    {
        if (is_null($columns)) {
            return $this->columns;
        }
        $classColumns = array_keys($this->className::getColumns());
        $diff = array_diff($columns, $classColumns);
        if (count($diff) > 0) {
            throw new Exception('columns have to belong ' . $this->className::getTableName() . ' table!');
        }
        $columns = array_fill_keys($classColumns, $this->columnPropertiesTemplate);
        $this->columns = new Config($columns);
        return $this;
    }


    /**
     * @param string $column
     * @return Std set column as pivot / get params this column
     * set column as pivot / get params this column
     * @throws Exception
     */
    public function setPivotColumn(string $column)
    {
        if (! $this->isColumnSet($column)) {
            throw new Exception('Before set column as pivot define it as table column');
        }
        $this->pivot = new Config([$column => $this->pivotColumnPropertiesTemplate]);
        return $this->pivot->$column;
    }

    /**
     * @param string $pivotColumn
     * @param SqlFilter|null $preFilter
     * @return Std return summary prefilter for column
     * set/get prefilter for decided pivot column
     */
    public function pivotPreFilter(string $pivotColumn, SqlFilter $preFilter = null) :Std
    {
        $this->validatePivotColumn($pivotColumn);
        if (is_null($preFilter)) {
            return $this->pivot->$pivotColumn->preFilter;
        }
        $this->pivot->$pivotColumn->preFilter = new Config($preFilter->toArray());
        return $this->pivot->$pivotColumn->preFilter;
    }

    /**
     * @param string $pivotColumn
     * @param array $sortColumns
     * @param string $direction
     * @return Std sort columns as property, direction as values
     * set/get sort columns and direction
     * @throws Exception
     */
    public function pivotSortBy(string $pivotColumn, array $sortColumns = null, string $direction = '') :Config
    {
        $this->validatePivotColumn($pivotColumn);
        if (is_null($sortColumns)) {
            return $this->pivot->$pivotColumn->sortBy;
        }
        $this->validateSortDirection($direction);
        $this->isColumnsDefined($sortColumns);
        $this->pivot->$pivotColumn->sortBy = new Config(array_fill_keys($sortColumns, strtolower($direction)));
        return $this->pivot->$pivotColumn->sortBy;
    }

    /**
     * @param string|integer $width
     * @param string $pivotColumn
     * @return string|integer width each item of columns based on pivot column
     */
    public function widthPivotItems(string $pivotColumn, $width)
    {

    }

    /**
     * @return mixed
     * return columns config
     */
    public function columnsConfig(): Std
    {
        // TODO: Implement columnsConfig() method.
    }

    /**
     * @param string $column
     * @param Std|null $config
     * @return mixed
     * if $config is null - return current config $column column
     * if $config is array - set config for $column column
     */
    public function columnConfig(string $column, Std $config = null)
    {
        // TODO: Implement columnConfig() method.
    }

    public function sortOrderSets($sortSets = null)
    {
        // TODO: Implement sortOrderSets() method.
    }

    public function sortBy(string $sortTemplate, string $direction = '')
    {
        // TODO: Implement sortBy() method.
    }

    public function setTablePreFilter(SqlFilter $condition)
    {
        // TODO: Implement setPreFilter() method.
    }

    public function setFilter(SqlFilter $condition)
    {
        // TODO: Implement setFilter() method.
    }

    public function addFilter(SqlFilter $condition, $appendMode)
    {
        // TODO: Implement addFilter() method.
    }

    public function isPivot($column) :bool
    {
        return isset($this->pivot->$column);
    }
    public function isColumnSet($column) :bool
    {
        return isset($this->columns->$column);
    }

    protected function validatePivotColumn($column)
    {
        if (! $this->isPivot($column)) {
            throw new Exception('Column \'' . $column . '\' doesn\'t set as pivot');
        }
        return true;
    }

    protected function isColumnsDefined(array $columns)
    {
        $classColumns = array_keys($this->className::getColumns());
        $diff = array_diff($columns, $classColumns);
        if (count($diff) > 0) {
            throw new Exception('columns have to belong ' . $this->className::getTableName() . ' table!');
        }
        return true;
    }

    protected function validateSortDirection($direct)
    {
        $direct = strtolower($direct);
        if ('asc' == $direct || 'desc' == $direct || '' == $direct) {
            return true;
        } else {
            throw new Exception('Allowed sort direction is: \'asc\' or \'desc\'');
        }
    }
}