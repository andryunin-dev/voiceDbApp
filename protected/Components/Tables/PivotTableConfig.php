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
 * @property Std $pivots
 * @property Std $columnPropertiesTemplate
 */
class PivotTableConfig extends TableConfig
    implements PivotTableConfigInterface
{

    const BASE_CONF_PATH = ROOT_PATH . DS . 'Configs' . DS;

    protected $pivotTablePropertyTemplate = [
        'pivot' => []
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
        'column' => '',
        'preFilter' => [], //preFilter for pivot column values
        'sortBy' => [], //sort columns and directions for pivot column ['column_1' => 'asc|desc', 'column_N' => 'asc|desc']
        'itemWidth' => 0, //width for each column from pivot values.
    ];

    public function __construct(string $tableName, string $class = null)
    {
        parent::__construct($tableName, $class);

        if(!empty($class)) {
            foreach ($this->pivotTablePropertyTemplate as $prop => $value) {
                $value = (is_array($value)) ? new Std($value) : $value;
                $this->$prop = $value;
            }
        }
    }

    public function columns(array $columns = null, array $extraColumns = null)
    {
        /*if arg is null - return list of columns as Std*/
        if (is_null($columns)) {
            $res = array_keys($this->getAllColumnsConfig()->toArray());
            return new Std($res);
        }
        $extraColumns = is_null($extraColumns) ? [] : $extraColumns;
        $classColumns = array_keys($this->className::getColumns());
        $pivotAliases = array_keys($this->pivot->toArray());
        $unionColumns = array_merge($classColumns, $extraColumns, $pivotAliases);
        $diff = array_diff($columns, $unionColumns);
        if (count($diff) > 0) {
            throw new Exception('columns have to belong ' . $this->className::getTableName() . ' table or is defined as extraColumns or is defined as pivot column!');
        }
        $this->extraColumns = new Std($extraColumns);
        $columns = array_fill_keys($columns, $this->columnPropertiesTemplate);
        $this->columns = new Std($columns);
        return $this;
    }

//    public function getAllColumnsConfig(): Std
//    {
//        $res = new Std();
//        foreach ($this->columns as $col => $colConf) {
//            if (! isset($this->pivots->$col)) {
//                $res->$col = $colConf;
//                continue;
//            }
//            $pivotItems = $this->findPivotItems($col);
//            foreach ($pivotItems as $idx => $item) {
//                $res->$item = new Std($this->columnPropertiesTemplate);
//                $res->$item->id = $idx . '_' . $item;
//                $res->$item->width = $this->pivotWidthItems($col);
//            }
//        }
//        return $res;
//    }

    /**
     * @param string $column
     * @param string|null $alias
     * @return Config set column as pivot / get params this column
     * @throws Exception
     * set column as pivot / get params this column
     * if $alias is null, one will set = $column
     * $alias has to be unique in pivot part of config
     */
    public function definePivotColumn(string $column, string $alias = null)
    {
        if (! $this->isColumnDefinedInClass($column)) {
            throw new Exception('Pivot column has to be one of class columns(properties)');
        }
        $alias = is_null($alias) ? $column : $alias;
        $this->pivot->$alias = new Std($this->pivotColumnPropertiesTemplate);
        $this->pivot->$alias->column = $column;
        return $this;
    }

    /**
     * @param string $pivColumnAlias
     * @param SqlFilter|null $preFilter
     * @return self|SqlFilter return summary prefilter for column
     * set/get preFilter for decided pivot column
     */
    public function pivotPreFilter(string $pivColumnAlias, SqlFilter $preFilter = null)
    {
        $this->validatePivotColumn($pivColumnAlias);
        if (is_null($preFilter)) {
            return (new SqlFilter($this->className()))
                ->setFilterFromArray($this->pivot->$pivColumnAlias->preFilter->toArray());
        }
        $this->pivot->$pivColumnAlias->preFilter = new Std($preFilter->toArray());
        return $this;
    }

    /**
     * @param string $pivColumnAlias
     * @param array $sortColumns
     * @param string $direction
     * @return Std|PivotTableConfig sort columns as property, direction as values
     * set/get sort columns and direction
     * @throws Exception
     */
    public function pivotSortBy(string $pivColumnAlias, array $sortColumns = null, string $direction = '')
    {
        $this->validatePivotColumn($pivColumnAlias);
        if (is_null($sortColumns)) {
            return $this->pivot->$pivColumnAlias->sortBy;
        }
        $this->validateSortDirection($direction);
        $this->areAllColumnsDefined($sortColumns);
        $this->pivot->$pivColumnAlias->sortBy = new Std(array_fill_keys($sortColumns, strtolower($direction)));
        return $this;
    }

    public function pivotSortByQuotedString(string $pivColumnAlias)
    {
        return $this->sortByToQuotedString($this->pivotSortBy($pivColumnAlias));
    }

    /**
     * @param string $pivColumnAlias
     * @param string|integer $width
     * @return self|int|string width each item of columns based on pivot column
     * @throws Exception
     */
    public function pivotWidthItems(string $pivColumnAlias, $width = null)
    {
        $this->validatePivotColumn($pivColumnAlias);
        if (is_null($width)) {
            return $this->pivot->$pivColumnAlias->itemWidth;
        }
        if(!is_string($width) && !is_int($width)) {
            throw new Exception('Width has to be int like 10 or string like 10px');
        }
        if(is_numeric($width)) {
            //width set in percents
            $this->pivot->$pivColumnAlias->itemWidth = intval($width);
            return $this;
        } elseif(is_string($width) && substr(trim(strtolower($width)), -2) == 'px') {
            $this->pivot->$pivColumnAlias->itemWidth = trim(strtolower($width));
            return $this;
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

    public function getPivots()
    {
        return $this->pivot;
    }

    /**
     * @param string $alias
     * @return Std
     * @throws Exception
     */
    public function getPivotColumnByAlias(string $alias)
    {
        $this->validatePivotColumn($alias);
        return $this->pivot->$alias;
    }

    protected function getColumnPropertiesTemplate()
    {
        return new Std($this->columnPropertiesTemplate);
    }

//    public function findPivotItems(string $alias)
//    {
//        if (false === $this->config->isPivot($alias)) {
//            return false;
//        }
//        $filter = $this->config->pivotPreFilter($alias)->mergeWith($this->filter, 'ignore');
//        $pivColumn = $this->config->getPivotColumnByAlias($alias);
//        $filterStatement = $filter->filterStatement;
//        $filterParams = $filter->filterParams;
//        $sortBy = $this->config->pivotSortByQuotedString($alias);
//        $query = (new Query())
//            ->select($pivColumn->column)
//            ->distinct()
//            ->from($this->config->className()::getTableName());
//        if (!empty($filterStatement)) {
//            $query
//                ->where($filterStatement);
//        }
//        if (!empty($sortBy)) {
//            $query->order($sortBy);
//        }
//        $query = $this->driver->makeQueryString($query);
//        $queryRes = $this->config->className::getDbConnection()->query($query, $filterParams)->fetchAll(\PDO::FETCH_COLUMN, 0);
//        return new Std($queryRes);
//    }

}