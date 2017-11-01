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
 * @property-read string $tableName
 * @property-read array $reportColumns
 * @property-read Config $reportColumnsConfig
 * @property-read array $pivotColumn
 * @property-read Config $pivotColumnConfig
 * @property-read array $pivotColumnValues
 */
class PivotReport2 extends Config
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
            $this->reportConf = new Config();
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
        $classColumns = array_keys($this->className::getColumns());
        //set list of columns for report
        if (empty($columns)) {
            throw new Exception('You have to set at least 2 columns (row column and pivot column) for report!');
        }
        $diff = array_diff($columns, $classColumns);
        if (count($diff) > 0) {
            throw new Exception('columns have to belong ' . $this->className::getTableName() . ' table!');
        }
        $this->reportColumnsConfig->name = new Config($columns);

        //set sort order
        $direction = ('asc' == strtolower($direction) || 'asc' == strtolower($direction)) ? strtoupper($direction) : '';
        if (empty($sortOrder)) {
            $sortOrder = $columns;
        }
        $diff = array_diff($sortOrder, $classColumns);
        if (count($diff) > 0) {
            throw new Exception('columns for sorting have to belong ' . $this->reportConf->className::getTableName() . ' table!');
        }
        foreach ($sortOrder as $column) {
            $this->reportColumnsConfig->sortOrder->$column = $direction;
        }
        return $this->reportColumnsConfig;
    }


    /**
     * @param string $colName
     * @param array $sortOrder
     * @param string $direction
     * @return Config
     * @throws Exception
     */
    public function setPivotColumn(string $colName, array $sortOrder = [], string $direction = '')
    {
        // set pivot column name
        if (false === $this->isReportColumnsSet()) {
            throw new Exception('Before assign pivot column you have to define report column set');
        }
        $classColumns = array_keys($this->className::getColumns());
        if (! in_array($colName, $this->reportColumns)) {
            throw new Exception('pivot column has to has been defined in Report Column Set!');
        }
        $this->pivotColumnConfig->name = new Config([$colName]);
        //unset pivot column from report columns config
        foreach ($this->reportColumnsConfig->name as $key => $reportColName) {
            if ($reportColName == $colName) {
                unset($this->reportColumnsConfig->name->$key);
            }
        }
        unset($this->reportColumnsConfig->sortOrder->$colName);

        //set sort order for pivot column values
        $direction = ('asc' == strtolower($direction) || 'asc' == strtolower($direction)) ? strtoupper($direction) : '';
        if (empty($sortOrder)) {
            $sortOrder[] = $colName;
        }
        $diff = array_diff($sortOrder, $classColumns);
        if (count($diff) > 0) {
            throw new Exception('columns for sorting have to belong ' . $this->reportConf->className::getTableName() . ' table!');
        }
        foreach ($sortOrder as $column) {
            $this->pivotColumnConfig->sortOrder->$column = $direction;
        }
        return $this->pivotColumnConfig;
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
        return $this->setFilter($this->pivotColumnConfig, $filter, $allowNull);
    }
    public function setReportColumnsFilter(array $filter = [], bool $allowNull = false)
    {
        //if pivot column isn't set yet, throw exception
        if (! $this->isReportColumnsSet()) {
            throw new Exception('Before set filter for report columns  column set them');
        }
        return $this->setFilter($this->reportColumnsConfig, $filter, $allowNull);
    }
    /**
     * @param Config $columnConfig
     * @param array $filter
     * @param boolean $allowNull допускать значения null в pivotColumn
     * @return Config
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
    protected function setFilter(Config $columnConfig, array $filter = [], bool $allowNull) {
        $columnConfig->filter->allowNull = $allowNull;
        $classColumns = array_keys($this->className::getColumns());
        $filterColumns = array_keys($filter);
        if (count(array_diff($filterColumns, $classColumns)) > 0) {
            throw new Exception('columns in filter array have to belong ' . $this->className::getTableName() . ' table!');
        }
        foreach ($filter as $column => $value) {
            $columnConfig->filter->conditions->$column = new Config(array_map('trim', explode(',', $value)));
        }
        return $columnConfig->filter;
    }

    /**
     * @param Config $columnConfig
     * @param string $columnPrefix
     * @param bool $asString //if true - result will as string with integrated params
     * @return array
     * build condition statement for sql query from filter config
     * you can define prefix for columns. This prefix will use as table name an will be quoted.
     *
     * return associated array with keys 'statement' and 'params'
     */
    protected function buildFilterStatement(Config $columnConfig, string $columnPrefix = '', $includeAllowNull = true)
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
        //append null statement only if $includeAllowNull = true
        if (true === $includeAllowNull && false === $columnConfig->filter->allowNull) {
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
        $result = [];
        $tableName = $this->driver->quoteName($this->tableName);

        //report columns with pivot column
        $reportColumns = $this->reportColumns;
        $reportColumns = array_map(function($col) { return $this->driver->quoteName($col); }, $reportColumns);


        //report sort columns (associated array like [col_name => sort_direct])
        $reportSortOrder = $this->reportColumnsConfig->sortOrder->toArray();
        foreach ($reportSortOrder as $col=> $direct) {
            unset($reportSortOrder[$col]);
            $reportSortOrder[$this->driver->quoteName($col)] = $direct;
        }

        //report sort columns only (without direction, numeric array)
        $reportSortOrderCols = array_keys($reportSortOrder);

        //pivot columns
        $pivotColumn = $this->pivotColumn;
        $pivotColumn = array_map(function($col) { return $this->driver->quoteName($col); }, $pivotColumn);

        $pivotColumnStr = array_pop($pivotColumn);

        //pivot sort columns (associated array like [col_name => sort_direct])
        $pivotSortOrder = $this->pivotColumnConfig->sortOrder->toArray();
        foreach ($pivotSortOrder as $col=> $direct) {
            unset($pivotSortOrder[$col]);
            $pivotSortOrder[$this->driver->quoteName($col)] = $direct;
        }

        //pivot sort columns only (without direction, numeric array)
        $pivotSortOrderCols = array_keys($pivotSortOrder);

        //pivot filter columns
        $pivotFilterCols = array_keys($this->pivotColumnConfig->filter->conditions->toArray());
        $pivotFilterCols = array_map(function($col) { return $this->driver->quoteName($col); }, $pivotFilterCols);

        //condition array (statement + params array) for pivot filter
        $pivotFilterCond = $this->buildFilterStatement($this->pivotColumnConfig);
        $pivotCond = [];
        if (!empty($pivotFilterCond)) {
            $pivotCond[] = $pivotFilterCond['statement'];
            $result['params'] = $pivotFilterCond['params'];
        }
        foreach ($reportColumns as $col) {
            $pivotCond[] = 't2' . '.' . $col . ' = ' . 't1' . '.' . $col;
        }
        $sql = 'SELECT' . "\n\t";
        foreach ($reportColumns as $col) {
            $sql .= $col . ',' . "\n\t";
        }
        $sql .= '(SELECT jsonb_object_agg(t3.' . $pivotColumnStr . ',' . ' t3.numbers)' . "\n\t";
        $sql .= 'FROM (' . "\n\t\t";
        $sql .= 'SELECT' . "\n\t\t\t";
        $sql .= $pivotColumnStr . ',' . "\n\t\t\t";
        $sql .= 'count(' . $pivotColumnStr . ') AS numbers' . "\n\t\t";
        $sql .= 'FROM ' . $tableName . '  AS t2' . "\n\t\t";
        $sql .= 'WHERE ' . implode(' AND ', $pivotCond) . "\n\t\t";
        $sql .= 'GROUP BY ' . 't2' . '.' . $pivotColumnStr . "\n\t\t";
        $order = [];
        foreach ($pivotSortOrder as $col => $direct) {
            $order[] = empty($direct) ? 't2' . '.' . $col : 't2' . '.' . $col . ' ' . $direct;
        }
        $sql .= 'ORDER BY ' . implode(', ', $order) . "\n\t";
        $sql .= ') AS t3' . "\n";
        $sql .= ') AS ' . $pivotColumnStr . "\n";
        $sql .= 'FROM ' . $tableName . ' AS t1' . "\n";
        $groupByCols = array_merge($reportColumns, $pivotFilterCols);
        $sql .= 'GROUP BY ' . implode(', ', $groupByCols) . "\n";
        if (false == $this->reportColumnsConfig->filter->allowNull) {
            $stmnt = $this->buildFilterStatement($this->pivotColumnConfig, '', false);
            $sql .= 'HAVING ' . $stmnt['statement'] . "\n";
        }
        $sql .= 'ORDER BY ' . implode(', ', $reportSortOrderCols);
        return $sql;
    }


    //getters and setters

    /**
     * @return Config
     */
    protected function getReportConfig()
    {
        return $this->reportConf;
    }

    /**
     * @return string
     */
    protected function getClassName()
    {
        return $this->reportConf->className;
    }
    /**
     * @return string
     */
    protected function getTableName()
    {
        return $this->reportConf->className::getTableName();
    }
    /**
     * @return array
     */
    protected function getReportColumns()
    {
        return $this->reportConf->columns->name->toArray();
    }

    /**
     * @return Config
     */
    protected function getReportColumnsConfig()
    {
        return $this->reportConf->columns;
    }

    /**
     * @return array
     * return once value as array only for compatibility
     */
    protected function getPivotColumn()
    {
        return $this->reportConf->pivotColumn->name->toArray();
    }
    protected function getPivotOrder()
    {
        return $this->reportConf->pivotColumn->sortOrder->toArray();
    }
    protected function getPivotColumnConfig()
    {
        return $this->reportConf->pivotColumn;
    }
    /**
     * @return array
     *
     * return array of pivot column values
     */
    protected function getPivotColumnValues()
    {
        $order = [];
        foreach ($this->pivotColumnConfig->sortOrder as $column => $direction) {
            $column = $this->driver->quoteName($column);
            $order[] = empty($direction) ? $column : $column . ' ' . $direction;
        }
        $order = implode(', ', $order);
        $columns = $this->pivotColumn;
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
    //======end getters setters
}