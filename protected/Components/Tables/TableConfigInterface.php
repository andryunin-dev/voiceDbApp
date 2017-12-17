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

    public function columns(array $columns = null,  array $extraColumns = null);
    public function columnList();
    public function columnConfig(string $column, Std $config = null);

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
     * @return string return sort order
     * return sort order
     */
    public function sortByQuotedString();
    /**
     * @param SqlFilter $preFilter
     * @return SqlFilter
     * return table preFilter
     * preFilter can't overwrite any operational filters
     */
    public function tablePreFilter(SqlFilter $preFilter = null);

    public function isColumnDefined($column) :bool ;

    /**
     * @param array|null $variantList
     * @return self
     */
    public function rowsOnPageList(array $variantList = null);

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