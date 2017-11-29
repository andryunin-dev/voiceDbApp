<?php
/**
 * Created by PhpStorm.
 * User: Dmitry
 * Date: 09.11.2017
 * Time: 11:06
 */

namespace App\Components\Tables;


use App\Components\Sql\SqlFilter;
use T4\Core\Exception;
use T4\Core\Std;

interface PivotTableConfigInterface extends TableConfigInterface
{
    /**
     * @param string $column
     * @return Std
     * this method only define what column will be pivot.
     * return config params for pivot column $column
     * set pivot column/get params pivot column
     * columns have to be set with method 'columns' before using this method
     * pivot column has to be one of columns defined with 'columns' method
     *
     * params for pivot column:
     * 'preFilter' - preFilter for pivot column values
     * 'sortBy' - sort columns and directions for pivot column ['column_1' => 'asc|desc', 'column_N' => 'asc|desc']
     * 'width' - width for each column from pivot columns set.
     *      If set in 'px' (ie '20px'), pivot column's width from columns properties will be ignored.
     *      If set in percents (ie 20), width for each column will be calculated by width from columns properties
     *
     */
    public function definePivotColumn(string $column);

    /**
     * @param string $pivotColumn
     * @param SqlFilter|null $condition
     * @return self|SqlFilter return summary prefilter for column
     * set/get prefilter for decided pivot column
     */
    public function pivotPreFilter(string $pivotColumn, SqlFilter $condition = null);

    /**
     * @param string $pivotColumnAlias
     * @param array $sortColumns
     * @param string $direction
     * @return Std sort columns as property, direction as values
     * set/get sort columns and direction
     */
    public function pivotSortBy(string $pivotColumnAlias, array $sortColumns = null, string $direction = '');

    /**
     * @param string $pivColumnAlias
     * @return string
     */
    public function pivotSortByQuotedString(string $pivColumnAlias);

    /**
     * @param $width
     * @param $pivotColumn
     * @return string|integer width each item of columns based on pivot column
     */
    public function pivotWidthItems(string $pivotColumn, $width);

    /**
     * @param $column
     * @return bool
     * if $column was defined as pivot, will return true.
     */
    public function isPivot($column) :bool ;

    public function getPivots();

    /**
     * @param string $alias
     * @return Std
     * @throws Exception
     */
//    public function getPivotColumnByAlias(string $alias);

    /**
     * @param string $alias
     * @return Std
     * return all items for pivot column
     */
//    public function findPivotItems(string $alias);
}