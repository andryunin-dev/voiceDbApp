<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use T4\Core\Config;
use T4\Core\Exception;
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

    public function dataUrl($url = null);
    public function tableWidth($width = null);
    public function tableHeight($height = null);

    /**
     * @param array $columns like [$column_name_1 => title, $column_name_2 => title, ]
     * All column_names have to belong a class that specified in construct method
     * @return self
     *
     * if $columns is null - return columns array for current table
     * if $columns is array - set columns set for current table
     * this method should be called first
     */
    public function columns(array $columns = null);

    public function getColumnsList();

    /**
     * @return mixed
     * return columns config
     */
    public function getAllColumnsConfig() : Std;

    /**
     * @param string $column
     * @param Std|null $config
     * @return self|Std
     */
    public function columnConfig(string $column, Std $config = null);
    public function getColumnConfig($column);

    public function appendColumnAlias(string $column, string $alias, string $operator = '');
    public function removeColumnAlias(string $alias);

    public function sortOrderSets(array $sortSets);

    /**
     * @param string $sortTemplate
     * @param string $direction
     *
     * This method define default sort order for table. This order will be saved with save() method
     * if $sortTemplate exists as set in sortOrderSets - apply this set
     * if not - tread $sortTemplate as column.
     * @return Config
     * @throws Exception
     */
    public function sortBy(string $sortTemplate, string $direction = '');

    /**
     * @return mixed
     * return sort order with directions
     */
    public function getSortOrder();

    /**
     * @return string
     * return sort order
     */
    public function getSortOrderAsQuotedString();
    /**
     * @param SqlFilter $preFilter
     * @return SqlFilter
     * return table preFilter
     * preFilter can't overwrite any operational filters
     */
    public function tablePreFilter(SqlFilter $preFilter = null);

    public function isColumnDefined($column) :bool ;

    /**
     * @param array|null $variantsList
     * @return self
     */
    public function rowsOnPageList(array $variantsList = null);

    /**
     * @return Std
     * return set of rowsOnPage as Std obj
     */
    public function getRowsOnPageList();

    /**
     * @return string
     * return class name for table
     */
    public function className();

    /*CSS Style methods*/
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssAddHeaderTableClasses($cssClass);
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssAddBodyTableClasses($cssClass);
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssAddFooterTableClasses($cssClass);
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssSetHeaderTableClasses($cssClass);
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssSetBodyTableClasses($cssClass);
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssSetFooterTableClasses($cssClass);
}