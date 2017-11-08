<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use T4\Core\Config;
use T4\Core\Exception;

class TableConfig extends Config
{
    const BASE_CONF_PATH = ROOT_PATH . DS . 'TablesConfigs' . DS;

    protected $driver;
    protected $table;

    /**
     * TableConfig constructor.
     * @param string $class
     * @param string $tableName
     */
    public function __construct(string $class, string $tableName)
    {

    }

    /**
     * @param array $columns ['SQL_field => tile]
     * set table columns and its order
     * this method should be called before setColumnAttribute()
     */
    public function columns(array $columns)
    {}

    /**
     * @param mixed $width column width. if int (10) - set width in percents, if string ('10px') - set width in pixels
     * @param bool $sortable
     * @param bool $filterable
     */
    public function columnAttribute($width, bool $sortable, bool $filterable)
    {}

    public function addTableCondition(SqlFilter $filter)
    {}

    /**
     * @param string $column column name for witch set sortOrder. Column has to be sortable
     * @param array $sortOrderList real columns list for sorting
     */
    public function addSortOrder(string $column, array $sortOrderList)
    {}

    /**
     * return SQL query as string
     */
    protected function getSelectStatement() :string
    {}

    /**
     * @return array params for SQL query
     */
    protected function getSelectParams() :array
    {}



    public function validateWidth($val)
    {
        $val = strtolower(trim($val));
        if(filter_var($val, FILTER_VALIDATE_INT)) {
            return true;
        } elseif ('px' == substr($val, -2)) {
            return true;
        } else {
            return false;
        }
    }

}