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
    public function __construct(string $class = null);
    public function addFilter(string $column, string $operator, array $values, $overwrite = false);
    public function setFilter(string $column, string $operator, array $values);
    public function removeFilter(string $column, string $operator);
    public function toArray();
    public function setFilterFromArray($data);
}