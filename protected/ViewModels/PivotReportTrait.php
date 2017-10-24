<?php

namespace App\ViewModels;
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
    protected static $valueCountMethods = [
        'count',
        'sum'
    ];
    protected static $pivotReportConfig = [
        'rowNamesColumn' => [
            'column' => false,
            'sqlType' => '',
            'orderBy' => '',
            'direction' => ''
        ],
        'pivotColumn' => [
            'column' => false,
            'sqlType' => '',
            'filter' => [],
            'filterWhereClause' => '',
            'excludeNull' => true,
            'orderBy' => '',
            'direction' => ''
        ],
        'valueColumn' => [
            'column' => false,
            'sqlType' => '',
            'orderBy' => '',
            'direction' => '',
            'countMethod' => 'count'
        ],
        'extraColumns' => [ ]
    ];

    public static function setRowNamesColumn(string $colName, string $sqlType, string $orderBy = '', string $direction = '')
    {
        $classColumns = self::getColumns();
        self::$pivotReportConfig['rowNamesColumn']['column'] = array_key_exists($colName, $classColumns) ? $colName : false;
        if (false !== self::$pivotReportConfig['rowNamesColumn']['column']) {
            self::$pivotReportConfig['rowNamesColumn']['orderBy'] = array_key_exists($orderBy, $classColumns) ? $orderBy : '';
            self::$pivotReportConfig['rowNamesColumn']['direction'] = ('asc' == strtolower($direction) || 'asc' == strtolower($direction))
                ? strtoupper($direction) : '';
            self::$pivotReportConfig['rowNamesColumn']['sqlType'] = $sqlType;
        }
    }
    public static function setPivotColumn(string $colName, string $sqlType, string $orderBy = '', string $direction = '')
    {
        $classColumns = self::getColumns();
        self::$pivotReportConfig['pivotColumn']['column'] = array_key_exists($colName, $classColumns) ? $colName : false;
        if (false !== self::$pivotReportConfig['rowNamesColumn']['column']) {
            self::$pivotReportConfig['pivotColumn']['orderBy'] = array_key_exists($orderBy, $classColumns) ? $orderBy : '';
            self::$pivotReportConfig['pivotColumn']['direction'] = ('asc' == strtolower($direction) || 'desc' == strtolower($direction))
                ? strtoupper($direction) : '';
            self::$pivotReportConfig['pivotColumn']['sqlType'] = $sqlType;
        }
    }

    /**
     * @param array $filter
     * @param boolean $excludeNull
     *
     * задает фильтр значений для pivotColumn
     * конкатенация:
     *      column_1 ... column_N условием AND
     *      val_1 ... val_N условием OR
     * формат: [
     *              'column_1' => [val_1, val_2...val_N],
     *              'column_2' => [val_1, val_2...val_N],
     *               ....
     *              'column_N' => [val_1, val_2...val_N]
     *         ]
     */
    public static function setPivotFilter(array $filter = [], bool $excludeNull = true) {
        self::$pivotReportConfig['pivotColumn']['excludeNull'] = $excludeNull;
        $classColumns = self::getColumns();
        self::$pivotReportConfig['pivotColumn']['filter'] = [];
        foreach ($filter as $column => $value) {
            if (in_array($column, $classColumns)) {
                self::$pivotReportConfig['pivotColumn']['filter'][] = $value;
            }
        }
        self::buildWhereClause();
    }
    protected static function buildWhereClause()
    {
        //Todo написать метод сборки выражения Where для фильтрации значений pivotColumn
    }
    public static function setValueColumn(string $colName, string $sqlType, string $orderBy = '', string $direction = '')
    {
        $classColumns = self::getColumns();
        self::$pivotReportConfig['valueColumn']['column'] = array_key_exists($colName, $classColumns) ? $colName : false;
        if (false !== self::$pivotReportConfig['rowNamesColumn']['column']) {
            self::$pivotReportConfig['valueColumn']['orderBy'] = array_key_exists($orderBy, $classColumns) ? $orderBy : '';
            self::$pivotReportConfig['valueColumn']['direction'] = ('asc' == strtolower($direction) || 'desc' == strtolower($direction))
                ? strtoupper($direction) : '';
            self::$pivotReportConfig['valueColumn']['sqlType'] = $sqlType;
        }

    }
    public static function setValueCountMethod(string $method = 'count')
    {
        if (in_array($method, self::$valueCountMethods)) {
            self::$pivotReportConfig['valueColumn']['countMethod'] = $method;
        }
    }

    public static function getRowNamesColumn()
    {
        return self::$pivotReportConfig['rowNamesColumn']['column'];
    }
    public static function getColNamesColumn()
    {
        return self::$pivotReportConfig['colNamesColumn']['column'];
    }
    public static function getValueColumn()
    {
        return self::$pivotReportConfig['valueColumn']['column'];
    }
    public static function getValueCountMethod()
    {
        return self::$pivotReportConfig['valueColumn']['countMethod'];
    }

    public static function reportColumns()
    {
        $res = [];
        if (false !== self::$pivotReportConfig['rowNamesColumn']['column']) {
            $res[] = self::$pivotReportConfig['rowNamesColumn']['column'];
        }
        if (! empty(self::$pivotReportConfig['extraColumns'])) {
            $res[] = array_merge($res, self::$pivotReportConfig['extraColumns']);
        }
        if (false !== self::$pivotReportConfig['colNamesColumn']['column']) {
            $column = self::$pivotReportConfig['colNamesColumn']['column'];
            $orderBy = empty(self::$pivotReportConfig['colNamesColumn']['orderBy']) ? $column : self::$pivotReportConfig['colNamesColumn']['orderBy'];
            $orderBy = empty(self::$pivotReportConfig['colNamesColumn']['direction']) ? $orderBy : $orderBy . ' ' . strtoupper(self::$pivotReportConfig['colNamesColumn']['direction']);
            $driver = self::getDbDriver();

            $query = (new Query())
                ->select($column)
                ->distinct()
                ->from(self::getTableName())
                ->where($driver->quoteName($column) . ' NOTNULL')
                ->order($driver->quoteName($orderBy));
            $query = $driver->makeQueryString($query);
            $queryRes = self::getDbConnection()->query($query)->fetchAll(\PDO::FETCH_COLUMN, 0);
            if (! empty($queryRes)) {
                $res = array_merge($res, $queryRes);
            }
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