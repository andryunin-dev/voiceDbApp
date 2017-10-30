<?php

namespace App\Components\Reports;

use T4\Core\Config;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\Connection;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class PivotReport
 * @package App\Components\Reports
 *
 * @property-read Std $rowNamesColumn
 * @property-read string $rowNamesColumnName
 * @property-read array $extraColumns
 * @property-read array $extraColumnsNames
 * @property-read Std $pivotColumn
 * @property-read string $pivotColumnName
 * @property-read array $pivotColumnValues
 * @property-read Std $valueColumn
 * @property-read string $valueColumnName
 */
class PivotReport extends Std
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
            $this->reportConf->pivotColumn = new Std();
            $this->reportConf->pivotColumn->filter = new Std();
            $this->reportConf->rowNamesColumn = new Std();
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

    public function setRowNamesColumn(string $colName, string $sqlType, string $orderBy = '', string $direction = '')
    {
        $classColumns = $this->reportConf->className::getColumns();
        if (! array_key_exists($colName, $classColumns)) {
            throw new Exception('column with row names has to be one of ' . $this->reportConf->className::getTableName() . ' table columns!');
        }
        $this->reportConf->rowNamesColumn->name = $colName;
        $this->reportConf->rowNamesColumn->orderBy = array_key_exists($orderBy, $classColumns) ? $orderBy : $colName;
        $this->reportConf->rowNamesColumn->direction = ('asc' == strtolower($direction) || 'asc' == strtolower($direction))
            ? strtoupper($direction) : '';
        $this->reportConf->rowNamesColumn->sqlType = $sqlType;
    }
    public function setPivotColumn(string $colName, string $sqlType, string $orderBy = '', string $direction = '')
    {
        $classColumns = $this->reportConf->className::getColumns();
        if (! array_key_exists($colName, $classColumns)) {
            throw new Exception('pivot column has to be one of ' . $this->reportConf->className::getTableName() . ' table columns!');
        }
        $this->reportConf->pivotColumn->name = $colName;
        $this->reportConf->pivotColumn->orderBy = array_key_exists($orderBy, $classColumns) ? $orderBy : $colName;
        $this->reportConf->pivotColumn->direction = ('asc' == strtolower($direction) || 'desc' == strtolower($direction))
            ? strtoupper($direction) : '';
        $this->reportConf->pivotColumn->sqlType = $sqlType;
    }
    protected function isPivotColumnSet()
    {
        return !empty($this->reportConf->pivotColumn->name);
    }
    public function setPivotFilter(array $filter = [], bool $allowNull = false)
    {
        $this->setFilter($this->reportConf->pivotColumn, $filter, $allowNull);
    }
    /**
     * @param Std $columnConfig
     * @param array $filter
     * @param boolean $allowNull допускать значения null в pivotColumn
     * @throws Exception if pivot column isn't set
     *
     * задает фильтр значений для pivotColumn
     * конкатенация:
     *      column_1 ... column_N условием AND
     *      val_1 ... val_N внутри каждой колонки - условием OR
     * формат: [
     *              'column_1' => [val_1, val_2...val_N],
     *              'column_2' => [val_1, val_2...val_N],
     *               ....
     *              'column_N' => [val_1, val_2...val_N]
     *         ]
     */
    protected function setFilter(Std $columnConfig, array $filter = [], bool $allowNull) {
        //if pivot column isn't set yet, throw exception
        if (! $this->isPivotColumnSet()) {
            throw new Exception('Before set filter for pivot column set one');
        }
        $columnConfig->filter->allowNull = $allowNull;
        $columnConfig->filter->conditions = new Config();
        $classColumns = $this->reportConf->className::getColumns();
        foreach ($filter as $column => $value) {
            if (array_key_exists($column, $classColumns)) {
                $columnConfig->filter->conditions->$column = $value;
            }
        }
    }

    /**
     * @param Std $columnConfig
     * @return mixed
     * statement WHERE for sql query to get PivotColumnNames
     */
    protected function buildFilterWhereStatement(Std $columnConfig)
    {

        $params = [];
        $columnsStatements = [];
        foreach ($columnConfig->filter->conditions as $column => $values) {
            $values = explode(',', $values);
            $columnStatements = [];
            foreach ($values as $index => $value) {
                $columnStatements[] = $this->driver->quoteName($column) . ' = ' . ':' . $column . '_' . $index;
                $params[':' . $column . '_' . $index] = $value;
            }
            $columnsStatements[$column] = '(' . implode(' OR ',$columnStatements) . ')';
        }
        //append null statement
        if (! $columnConfig->filter->allowNull) {
            $columnsStatements[] = $this->driver->quoteName($columnConfig->name) . ' NOTNULL';
        }
        $result['statement'] = implode(' AND ', $columnsStatements);
        $result['params'] = $params;
        return $result;
    }

    /**
     * @param Std $columnConfig
     * @return string
     *
     * PostgreSQL only!
     */
    protected function buildStringFilterWhereStatement(Std $columnConfig)
    {
        /**
         * @var Connection $connection
         */
        $connection = $this->reportConf->className::getDbConnection();
        $columnsStatements = [];
        foreach ($columnConfig->filter->conditions as $column => $values) {
            $values = explode(',', $values);
            $columnStatements = [];
            foreach ($values as $index => $value) {
                $columnStatements[] = $this->driver->quoteName($column) . ' = ' . $connection->quote($value);
            }
            $columnsStatements[$column] = '(' . implode(' OR ',$columnStatements) . ')';
        }
        //append null statement
        if (! $columnConfig->filter->allowNull) {
            $columnsStatements[] = $this->driver->quoteName($columnConfig->name) . ' NOTNULL';
        }
        $result = implode(' AND ', $columnsStatements);
        return $result;
    }
    public function setValueColumn(string $colName, string $sqlType, $countMethod = 'count')
    {
        $classColumns = $this->reportConf->className::getColumns();
        if (! array_key_exists($colName, $classColumns)) {
            throw new Exception('column with row names has to be one of ' . $this->reportConf->className::getTableName() . ' table columns!');
        }
        $this->reportConf->valueColumn->name = $colName;
        $this->reportConf->valueColumn->sqlType = $sqlType;
        $this->reportConf->valueColumn->countMethod = in_array($countMethod, self::$countMethods) ? $countMethod : self::$defaultCountMethod;
    }
    public function setExtraColumns(array $columns)
    {
        $extra = [];
        $classColumns = $this->reportConf->className::getColumns();
        foreach ($columns as $name => $type) {
            if (array_key_exists($name, $classColumns)) {
                $extra[$name] = $type;
            }
        }
        if (count($extra) > 0) {
            $this->reportConf->extraColumns = $extra;
        }
    }

    //query builds
    public static function buildSelectPivotTableQuery()
    {

    }
    public function buildSourceSql()
    {
        $sourceSqlColumns = [];
        $sourceSqlColumns[] = $this->rowNamesColumnName;
        $sourceSqlColumns[] = implode(', ', $this->extraColumnsNames);
        $sourceSqlColumns[] = $this->pivotColumnName;
        $sourceSqlColumns[] = $this->valueColumnName;
        $order[] = $this->rowNamesColumn->orderBy;
        $order = array_merge($order, $this->extraColumnsNames);
        foreach ($sourceSqlColumns as $key => $column) {
            $sourceSqlColumns[$key] = $this->driver->quoteName($column);
        }
        foreach ($order as $key => $column) {
            $order[$key] = $this->driver->quoteName($column);
        }
        $pivotFilterStatement = $this->buildStringFilterWhereStatement($this->pivotColumn);
        $pivotColumnValues = implode(', ', $this->pivotColumnValues);
        $sourceSql = 'SELECT ' . implode(', ', $sourceSqlColumns) . "\n" .
            'FROM ' . $this->sourceTableName() . "\n" .
            'WHERE ' . $pivotFilterStatement . "\n" .
            'ORDER BY' . implode(', ', $order);

    }
    //getters and setters
    protected function getReportConfig()
    {
        return $this->reportConf;
    }
    protected function sourceTableName()
    {
        return $this->reportConf->className::getTableName();
    }
    /**
     * @return array
     */
    protected function getRowNamesColumn()
    {
        return $this->reportConf->rowNamesColumn;
    }

    /**
     * @return string
     */
    protected function getRowNamesColumnName()
    {
        return $this->rowNamesColumn->name;
    }
    protected function getPivotColumn()
    {
        return $this->reportConf->pivotColumn;
    }
    protected function getPivotColumnName()
    {
        return $this->pivotColumn->name;
    }

    protected function getValueColumn()
    {
        return $this->reportConf->valueColumn;
    }
    protected function getValueColumnName()
    {
        return $this->valueColumn->name;
    }

    /**
     * @return array
     *
     * return associated array like [column_name => sqlType]
     */
    protected function getExtraColumns()
    {
        return $this->reportConf->extraColumns;
    }

    /**
     * @return array
     *
     * return extra columns names array
     */
    protected function getExtraColumnsNames()
    {
        return array_keys($this->reportConf->extraColumns);
    }
    protected function getPivotColumnValues()
    {
        $query = (new Query())
            ->select($this->reportConf->pivotColumn->name)
            ->distinct()
            ->from($this->reportConf->className::getTableName())
            ->order($this->driver->quoteName($this->reportConf->pivotColumn->orderBy));

        $where = $this->buildFilterWhereStatement($this->reportConf->pivotColumn);

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