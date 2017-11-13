<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use T4\Core\Std;

interface TableConfigInterface
{
    /**
     * TableConfigInterface constructor.
     * @param string $tableName
     * @param string|null $class
     * if $class is null, find config with name $tableName and return it
     * else try to create new config with name $tableName for class $class
     */
    public function __construct(string $tableName, string $class = null);

    public function save();
    public function delete();

    /**
     * @param array $columns like [$column_name_1 => title, $column_name_2 => title, ]
     * All column_names have to belong a class that specified in construct method
     * @return mixed
     *
     * if $columns is null - return columns array for current table
     * if $columns is array - set columns set for current table
     * this method should be called first
     */
    public function columns(array $columns = null);

    /**
     * @return mixed
     * return columns config
     */
    public function allColumnsConfig() : Std;

    /**
     * @param string $column
     * @param Std|null $config
     * @return mixed
     * if $config is null - return current config $column column
     * if $config is array - set config for $column column
     */
    public function columnConfig(string $column, Std $config = null);

    public function sortOrderSets(array $sortSets = null);
    public function sortBy(string $sortTemplate, string $direction = '');
    public function tablePreFilter(Std $preFilter);

    public function isColumnSet($column) :bool ;
}