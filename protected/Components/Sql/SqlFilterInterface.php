<?php
/**
 * Created by PhpStorm.
 * User: karasev-dl
 * Date: 13.11.2017
 * Time: 18:00
 */

namespace App\Components\Sql;


interface SqlFilterInterface
{
    public function __construct(string $className);
    public function addFilter(string $column, string $operator, array $values, $overwrite = false);
    public function addFilterFromArray($data, $overwrite = false);
    public function setFilter(string $column, string $operator, array $values);

    /**
     * @param $data
     * @return SqlFilter
     * set filter from array/ Array has to be like:
     * [
     *      'column_1' => [
     *                      'op_1' => ['val_1', 'val_N']
     *                  ],
     *      'column_N' => [
     *                      'op_1' => ['val_1', 'val_N'],
     *                      'op_N' => ['val_1', 'val_N'],
     *                  ],
     * ]
     */
    public function setFilterFromArray($data);
    public function removeFilter(string $column, string $operator);
    public function toArray();

    /**
     * @return string converted to string filter to using in WHERE clause
     */
    public function filterStatement();

    /**
     * @return array parameters for filter statement
     */
    public function filterParams();
}