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
     * @param string|null $alias
     * @param bool $display
     * @return self this method only define what column will be pivot.
     */
    public function definePivotColumn(string $column, string $alias = null, bool $display = true);

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

    /**
     * @return Std
     * return this->pivot branch
     */
    public function pivots();

    /**
     * @param string $alias
     * @return Std
     * @throws Exception
     */
    public function pivotColumnByAlias(string $alias);

}