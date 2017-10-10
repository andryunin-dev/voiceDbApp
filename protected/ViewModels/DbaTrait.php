<?php
/**
 * Created by PhpStorm.
 * User: Dmitry
 * Date: 20.09.2017
 * Time: 1:10
 */

namespace App\ViewModels;


use T4\Dbal\Query;

trait DbaTrait
{
    public static function findAllDistictColumnValues(Query $query)
    {
        $res = self::getDbConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        $res = array_map(function ($item) {
            return array_pop($item);
        }, $res);
        return $res;
    }
}