<?php

namespace App\ViewModels;
use T4\Core\Config;
use T4\Core\Exception;
use T4\Dbal\IDriver;
use T4\Dbal\Query;

/**
 * Trait PivotReportTrait
 * @package App\ViewModels
 *
 * @property string $rowNamesColumn
 * @property string $colNamesColumn
 * @property string $valueColumn
 * @property string $valueCountMethod
 * @property array $extraColumns
 */
trait PivotReportTrait
{
    protected static $reportConfPath = ROOT_PATH_PROTECTED . DS . 'pivotReportsConfig.php';
    protected static $countMethods = [
        'count',
        'sum'
    ];
    protected static $defaultCountMethod = 'count';
    protected static $reportsConf;
    protected static $reportConf;
    /**
     * @var IDriver $driver
     */
    protected static $driver;

    public static function initReport()
    {
        self::$reportsConf = (new Config(self::$reportConfPath));
        if (! isset(self::$reportsConf->{self::class})) {
            self::$reportsConf->{self::class} = new Config();
        }
        //get config for current class
        self::$reportConf = self::$reportsConf->{self::class};
        if (! isset(self::$reportConf->pivotColumn)) {
            self::$reportConf->pivotColumn = new Config();
        }
        if (! isset(self::$reportConf->pivotColumn->filter)) {
            self::$reportConf->pivotColumn->filter = new Config();
        }
        if (! isset(self::$reportConf->rowNamesColumn)) {
            self::$reportConf->rowNamesColumn = new Config();
        }
        self::$driver = self::getDbDriver();
    }

    public static function saveReportConf()
    {
        self::$reportsConf->save();
    }


    public static function reportConfig()
    {
        return self::$reportConf;
    }

    protected static function isPivotColumnSet()
    {
        return ! empty(self::$reportConf->pivotColumn->name);
    }

    public static function setPivotColumn(string $colName, string $sqlType, string $orderBy = '', string $direction = '')
    {
        $classColumns = self::getColumns();
        if (! array_key_exists($colName, $classColumns)) {
            throw new Exception('pivot column has to be one of ' . self::getTableName() . ' table columns!');
        }

        self::$reportConf->pivotColumn->name = $colName;
        self::$reportConf->pivotColumn->orderBy = array_key_exists($orderBy, $classColumns) ? $orderBy : $colName;
        self::$reportConf->pivotColumn->direction = ('asc' == strtolower($direction) || 'desc' == strtolower($direction))
            ? strtoupper($direction) : '';
        self::$reportConf->pivotColumn->sqlType = $sqlType;
    }
    public static function setRowNamesColumn(string $colName, string $sqlType, string $orderBy = '', string $direction = '')
    {
        $classColumns = self::getColumns();
        if (! array_key_exists($colName, $classColumns)) {
            throw new Exception('column with row names has to be one of ' . self::getTableName() . ' table columns!');
        }
        self::$reportConf->rowNamesColumn->name = $colName;
        self::$reportConf->rowNamesColumn->orderBy = array_key_exists($orderBy, $classColumns) ? $orderBy : $colName;
        self::$reportConf->rowNamesColumn->direction = ('asc' == strtolower($direction) || 'asc' == strtolower($direction))
            ? strtoupper($direction) : '';
        self::$reportConf->rowNamesColumn->sqlType = $sqlType;
    }
    public static function setValueColumn(string $colName, string $sqlType, $countMethod = 'count')
    {
        $classColumns = self::getColumns();
        if (! array_key_exists($colName, $classColumns)) {
            throw new Exception('column with row names has to be one of ' . self::getTableName() . ' table columns!');
        }
        self::$reportConf->valueColumn->name = $colName;
        self::$reportConf->valueColumn->sqlType = $sqlType;
        self::$reportConf->valueColumn->countMethod = in_array($countMethod, self::$countMethods) ? $countMethod : self::$defaultCountMethod;
    }

    /**
     * @param array $columns
     *
     * 'columnName' => 'sqlType'
     */
    public static function setExtraColumn(array $columns)
    {
        $extra = [];
        $classColumns = self::getColumns();
        foreach ($columns as $name => $type) {
            if (array_key_exists($name, $classColumns)) {
                $extra[$name] = $type;
            }
        }
        if (count($extra) > 0) {
            self::$reportConf->extraColumns = $extra;
        }
    }

    public static function setPivotFilter(array $filter = [], bool $allowNull = false)
    {
        self::setFilter(self::$reportConf->pivotColumn, $filter, $allowNull);
    }

    /**
     * @param Config $columnConfig
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
    protected static function setFilter(Config $columnConfig, array $filter = [], bool $allowNull) {
        //if pivot column isn't set yet, throw exception
        if (! self::isPivotColumnSet()) {
            throw new Exception('Before set filter for pivot column set one');
        }
        $columnConfig->filter->allowNull = $allowNull;
        $columnConfig->filter->conditions = new Config();
        $classColumns = self::getColumns();
        foreach ($filter as $column => $value) {
            if (array_key_exists($column, $classColumns)) {
                $columnConfig->filter->conditions->$column = $value;
            }
        }
    }

    protected static function buildFilterWhereStatement(Config $columnConfig)
    {

        $params = [];
        $columnsStatements = [];
        foreach ($columnConfig->filter->conditions as $column => $values) {
            $values = explode(',', $values);
            $columnStatements = [];
            foreach ($values as $index => $value) {
                $columnStatements[] = self::$driver->quoteName($column) . ' = ' . ':' . $column . '_' . $index;
                $params[':' . $column . '_' . $index] = $value;
            }
            $columnsStatements[$column] = '(' . implode(' OR ',$columnStatements) . ')';
        }
        //append null statement
        if (! $columnConfig->filter->allowNull) {
            $columnsStatements[] = self::$driver->quoteName($columnConfig->name) . ' NOTNULL';
        }
        $result['statement'] = implode(' AND ', $columnsStatements);
        $result['params'] = $params;
        return $result;
    }

    public static function rowNamesColumn()
    {
        return self::$reportConf->rowNamesColumn;
    }
    public static function pivotColumn()
    {
        return self::$reportConf->pivotColumn;
    }
    public static function valueColumn()
    {
        return self::$reportConf->valueColumn;
    }
    public static function extraColumns()
    {
        return self::$reportConf->extraColumns;
    }


    public static function pivotColumnNames($withExtraColumns = true)
    {
        $res[] = self::rowNamesColumn()->name;
        if (true === $withExtraColumns && ! empty(self::extraColumns())) {
            foreach (self::extraColumns() as $key => $value) {
                $res[] = $key;
            }
        }
        $query = (new Query())
            ->select(self::pivotColumn()->name)
            ->distinct()
            ->from(self::getTableName())
            ->order(self::$driver->quoteName(self::pivotColumn()->orderBy));

        $where = self::buildFilterWhereStatement(self::pivotColumn());

        if (! empty($where['statement'])) {
            $query->where($where['statement']);
        }
        $params = $where['params'];

        $query = self::$driver->makeQueryString($query);
        $queryRes = self::getDbConnection()->query($query, $params)->fetchAll(\PDO::FETCH_COLUMN, 0);
        if (! empty($queryRes)) {
            $res = array_merge($res, $queryRes);
        }
        return $res;
    }
    public static function buildSelectQuery()
    {

    }

    public static function reportFindAll()
    {}
    public static function reportFindAllByQuery($query, $params = [])
    {}
    public static function reportFindByQuery($query, $params = [])
    {}

    public static function getColNames()
    {}
    public static function getRowNames()
    {}
    public static function getCountColNames()
    {}
    public static function getCountRowNames()
    {}
}