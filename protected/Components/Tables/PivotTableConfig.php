<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use T4\Core\Config;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Orm\Model;

/**
 * Class PivotTableConfig
 * @package App\Components\Tables
 *
 * @property Config $pivots
 */
class PivotTableConfig extends TableConfig
    implements PivotTableConfigInterface
{
    use TableConfigTrait;

    const BASE_CONF_PATH = ROOT_PATH . DS . 'Configs' . DS;

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
        'column' => '',
        'preFilter' => [], //preFilter for pivot column values
        'sortBy' => [], //sort columns and directions for pivot column ['column_1' => 'asc|desc', 'column_N' => 'asc|desc']
        'width' => 0, //width for each column from pivot values.
    ];

    public function __construct(string $tableName, string $class = null)
    {
        parent::__construct($tableName, $class);
        $this->pivot = new Config();
    }

    /**
     * @param string $column
     * @param string|null $alias
     * @return Config set column as pivot / get params this column
     * @throws Exception
     * set column as pivot / get params this column
     * if $alias is null, one will set = $column
     * $alias has to be unique in pivot part of config
     */
    public function setPivotColumn(string $column, string $alias = null)
    {
        if (! $this->isColumnSet($column)) {
            throw new Exception('Before set column as pivot define it as table column');
        }
        $alias = is_null($alias) ? $column : $alias;
        $this->pivot->$alias = new Config($this->pivotColumnPropertiesTemplate);
        $this->pivot->$alias->column = $column;
        return $this->pivot;
    }

    /**
     * @param string $pivColumnAlias
     * @param SqlFilter|null $preFilter
     * @return Std return summary prefilter for column
     * set/get prefilter for decided pivot column
     * @internal param string $pivotColumn
     */
    public function pivotPreFilter(string $pivColumnAlias, SqlFilter $preFilter = null) :Std
    {
        $this->validatePivotColumn($pivColumnAlias);
        if (is_null($preFilter)) {
            return $this->pivot->$pivColumnAlias->preFilter;
        }
        $this->pivot->$pivColumnAlias->preFilter = new Config($preFilter->toArray());
        return $this->pivot->$pivColumnAlias->preFilter;
    }

    /**
     * @param string $pivColumnAlias
     * @param array $sortColumns
     * @param string $direction
     * @return Config sort columns as property, direction as values
     * set/get sort columns and direction
     * @throws Exception
     */
    public function pivotSortBy(string $pivColumnAlias, array $sortColumns = null, string $direction = '') :Config
    {
        $this->validatePivotColumn($pivColumnAlias);
        if (is_null($sortColumns)) {
            return $this->pivot->$pivColumnAlias->sortBy;
        }
        $this->validateSortDirection($direction);
        $this->isAllColumnsSet($sortColumns);
        $this->pivot->$pivColumnAlias->sortBy = new Config(array_fill_keys($sortColumns, strtolower($direction)));
        return $this->pivot->$pivColumnAlias->sortBy;
    }

    /**
     * @param string $pivColumnAlias
     * @param string|integer $width
     * @return int|string width each item of columns based on pivot column
     * @throws Exception
     */
    public function widthPivotItems(string $pivColumnAlias, $width = null)
    {
        $this->validatePivotColumn($pivColumnAlias);
        if (is_null($width)) {
            return $this->pivot->$pivColumnAlias->width;
        }
        if(!is_string($width) && !is_int($width)) {
            throw new Exception('Width has to be int like 10 or string like 10px');
        }
        if(is_numeric($width)) {
            //width set in percents
            $this->pivot->$pivColumnAlias->width = intval($width);
            return $this->pivot->$pivColumnAlias->width;
        } elseif(is_string($width) && substr(trim(strtolower($width)), -2) == 'px') {
            $this->pivot->$pivColumnAlias->width = trim(strtolower($width));
            return $this->pivot->$pivColumnAlias->width;
        } else {
            //Incorrect value of width
            throw new Exception('Width has to be int like 10 or string like 10px');
        }
    }

    /**
     * @param $columnAlias
     * @return bool
     *
     * return true if column is registered as pivot with alias $columnAlias
     */
    public function isPivot($columnAlias) :bool
    {
        return isset($this->pivot->$columnAlias);
    }

    /**
     * @param $columnAlias
     * @return bool
     * @throws Exception
     *
     * check pivot column by alias
     */
    protected function validatePivotColumn($columnAlias)
    {
        if (! $this->isPivot($columnAlias)) {
            throw new Exception('Column with alias \'' . $columnAlias . '\' doesn\'t set as pivot');
        }
        return true;
    }

    protected function getPivots()
    {
        return $this->pivot;
    }
}