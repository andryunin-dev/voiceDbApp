<?php

namespace App\Components\Reports;

use T4\Core\Config;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\Connection;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class PivotReport2
 * @package App\Components\Reports
 *
 * @property-read Config $reportConfig
 * @property-read string $className
 * @property-read array $reportColumns
 * @property-read Config $reportColumnsConfig
 * @property-read array $pivotColumn
 * @property-read Config $pivotColumnConfig
 * @property-read array $pivotColumnValues
 */
class PivotReport2 extends Std
{
    const REPORT_CONF_PATH = ROOT_PATH_PROTECTED . DS . 'pivotReportsConfig.php';

    protected static $countMethods = [
        'count',
        'sum'
    ];
    protected static $defaultCountMethod = 'count';
    protected $driver; //возможно не нужна
    protected $reportConf;
    protected $reportName;

    public function __construct(string $reportName, string $className = null)
    {
        parent::__construct();
        $this->reportConf = (new Config(self::REPORT_CONF_PATH))->$reportName;
        if (empty($this->reportConf) && empty($className)) {
            throw new Exception('Config for report "' . $reportName . '" is not exists and className is not present for creating a new config');
        }

        if (empty($this->reportConf)) {
            if (! class_exists($className)) {
                throw new Exception('Class ' . $className . ' is not exists');
            }
            if (get_parent_class($className) != Model::class) {
                throw new Exception('Class for report must extends Model class');
            }
            $this->reportConf = new Std();
            $this->reportConf->className = $className;
            $this->reportConf->pivotColumn = new Config();
            $this->reportConf->columns = new Config();
        }
        $this->reportName = $reportName;
        $this->driver = $this->reportConf->className::getDbDriver();
    }

    public function save()
    {
        $allReports = new Config(self::REPORT_CONF_PATH);
        $allReports->{$this->reportName} = $this->reportConf;
        $allReports->save();
    }

    public function delete()
    {
        $allReports = new Config(self::REPORT_CONF_PATH);
        unset($allReports->{$this->reportName});
        $allReports->save();
    }

    //задание колонок, участвующих в отчете
    public function setReportColumns(array $columns, array $sortOrder = [], $direction = '')
    {
        $classColumns = $this->reportConf->className::getColumns();
        //set list of columns for report
        $list = [];
        foreach ($columns as $column) {
            if (array_key_exists($column, $classColumns)) {
                $list[] = $column;
            } else {
                throw new Exception('columns have to belong ' . $this->reportConf->className::getTableName() . ' table!');
            }
        }
        if (empty($list)) {
            throw new Exception('You have to set at least 2 columns (row column and pivot column) for report!');
        }
        $this->reportConf->columns->name = new Config($list);

        //set sort order
        $direction = ('asc' == strtolower($direction) || 'asc' == strtolower($direction)) ? strtoupper($direction) : '';
        if (empty($sortOrder)) {
            $sortOrder = $this->reportConf->columns->name;
        }
        $list = [];
        foreach ($sortOrder as $column) {
            if (array_key_exists($column, $classColumns)) {
                $list[$column] = $direction;
            } else {
                throw new Exception('columns for sorting have to belong ' . $this->reportConf->className::getTableName() . ' table!');
            }
        }
        $this->reportConf->columns->sortOrder = $list;
    }

    /**
     * @param string $colName
     * @param string $sortOrder
     * @param string $direction
     * @throws Exception
     */
    public function setPivotColumn(string $colName, string $sortOrder = '', string $direction = '')
    {
        if (false === $this->isReportColumnsSet()) {
            throw new Exception('Before assign pivot column you have to define report column set');
        }
        $classColumns = $this->reportConf->className::getColumns();
        $repCol = $this->reportColumns;
        if (! in_array($colName, $this->getReportColumns())) {
            throw new Exception('pivot column has to has been defined in Report Column Set!');
        }
        if (!empty($orderBy) && !array_key_exists($colName, $classColumns)) {
            throw new Exception('column for sorting has to belong ' . $this->reportConf->className::getTableName() . ' table!');
        }
        $this->reportConf->pivotColumn->name = new Config([$colName]);
        //set sort order
        $direction = ('asc' == strtolower($direction) || 'asc' == strtolower($direction)) ? strtoupper($direction) : '';
        $sortOrder = empty($sortOrder) ? $this->pivotColumn : array_map('trim', explode(',',$sortOrder));
        foreach ($sortOrder as $column) {
            $this->reportConf->pivotColumn->sortOrder->$column = $direction;
        }
//        $this->reportConf->pivotColumn->sortOrder = [$sortOrder => $direction];
    }
    protected function isPivotColumnSet()
    {
        return !empty($this->reportConf->pivotColumn->name);
    }
    protected function isReportColumnsSet()
    {
        return !empty($this->reportConf->columns->name);
    }
    public function setPivotFilter(array $filter = [], bool $allowNull = false)
    {
        //if pivot column isn't set yet, throw exception
        if (! $this->isPivotColumnSet()) {
            throw new Exception('Before set filter for pivot column set one');
        }
        $this->setFilter($this->reportConf->pivotColumn, $filter, $allowNull);
    }
    public function setReportColumnsFilter(array $filter = [], bool $allowNull = false)
    {
        //if pivot column isn't set yet, throw exception
        if (! $this->isReportColumnsSet()) {
            throw new Exception('Before set filter for report columns  column set them');
        }
        $this->setFilter($this->reportConf->columns, $filter, $allowNull);
    }
    /**
     * @param Std $columnConfig
     * @param array $filter
     * @param boolean $allowNull допускать значения null в pivotColumn
     * @throws Exception if pivot column isn't set
     *
     * задает фильтр значений для выборки
     * поддерживатся только eq(=) оператор
     * конкатенация:
     *      column_1 ... column_N условием AND
     *      val_1 ... val_N внутри каждой колонки - условием OR
     * формат фильтра: [
     *              'column_1' => [val_1, val_2...val_N],
     *              'column_2' => [val_1, val_2...val_N],
     *               ....
     *              'column_N' => [val_1, val_2...val_N]
     *         ]
     */
    protected function setFilter(Std $columnConfig, array $filter = [], bool $allowNull) {
        $columnConfig->filter->allowNull = $allowNull;
        $classColumns = $this->reportConf->className::getColumns();
        $conditions = [];
        foreach ($filter as $column => $value) {
            if (array_key_exists($column, $classColumns)) {
                $conditions[$column] = array_map('trim', explode(',', $value));
            }
        }
        $columnConfig->filter->conditions = $conditions;
    }

    /**
     * @param Std $columnConfig
     * @param string $columnPrefix
     * @return array
     * build condition statement for sql query from filter config
     * you can define prefix for columns. This prefix will use as table name an will be quoted.
     */
    protected function buildFilterStatement(Std $columnConfig, string $columnPrefix = '')
    {
        $params = [];
        $columnsStatements = [];
        $columnPrefix = empty($columnPrefix) ? '' : $this->driver->quoteName($columnPrefix) . '.';
        foreach ($columnConfig->filter->conditions as $column => $values) {
            $currentColumnStatements = [];
            foreach ($values as $index => $value) {
                $currentColumnStatements[] = $columnPrefix . $this->driver->quoteName($column) . ' = ' . ':' . $column . '_' . $index;
                $params[':' . $column . '_' . $index] = $value;
            }
            if (empty($currentColumnStatements)) {
                continue;
            }
            $columnsStatements[] = (1 == count($currentColumnStatements)) ?
                array_pop($currentColumnStatements) :
                '(' . implode(' OR ',$currentColumnStatements) . ')';
        }
        //append null statement
        if (false === $columnConfig->filter->allowNull) {
            foreach ($columnConfig->name as $column) {
                $columnsStatements[] = $this->driver->quoteName($column) . ' NOTNULL';
            }
        }
        $result['statement'] = implode(' AND ', $columnsStatements);
        $result['params'] = $params;
        return $result;
    }

    //query builds
    public function buildSelectQuery()
    {
        $columnSet = $this->buildFilterStatement($this->getPivotColumnConfig(), 't2');
        return $columnSet;
    }
    protected function getPivotColumnValues()
    {
        $order = [];
        foreach ($this->pivotColumnConfig->sortOrder as $column => $direction) {
            $column = $this->driver->quoteName($column);
            $order[] = empty($direction) ? $column : $column . ' ' . $direction;
        }
        $order = implode(', ', $order);
        $columns = $this->pivotColumn;
//        $columns = ($columns instanceof Std) ? $columns->toArray() : $columns;
        $query = (new Query())
            ->select($columns)
            ->distinct()
            ->from($this->className::getTableName())
            ->order($order);

        $where = $this->buildFilterStatement($this->pivotColumnConfig);

        if (! empty($where['statement'])) {
            $query->where($where['statement']);
        }
        $params = $where['params'];

        $query = $this->driver->makeQueryString($query);
        $queryRes = $this->reportConf->className::getDbConnection()->query($query, $params)->fetchAll(\PDO::FETCH_COLUMN, 0);
        return $queryRes;
    }

    //getters and setters
    protected function getReportConfig()
    {
        return $this->reportConf;
    }
    protected function getClassName()
    {
        return $this->reportConf->className;
    }
    /**
     * @return array
     */
    protected function getReportColumns()
    {
        return ($this->reportConf->columns->name instanceof Std) ?
            $this->reportConf->columns->name->toArray() :
            $this->reportConf->columns->name;
    }
    protected function getReportColumnsConfig()
    {
        return $this->reportConf->columns;
    }
    protected function getPivotColumn()
    {
        return ($this->reportConf->pivotColumn->name instanceof Std) ?
            $this->reportConf->pivotColumn->name->toArray() :
            $this->reportConf->pivotColumn->name;
    }
    protected function getPivotColumnConfig()
    {
        return $this->reportConf->pivotColumn;
    }
    //======end getters setters
}