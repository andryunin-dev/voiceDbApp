<?php

namespace App\ViewModels;

use App\Components\SqlFilter;
use T4\Orm\Model;

trait ViewHelperTrait
{
    /**
     * принимаем список свойств для отображения
     * возвращаем массив c названием полей в таблице модели
     * те поля, которых нет в таблице игнорируются
     *
     * @param string|array $list //Список свойств модели, которые надо отображать
     * @return boolean|array
     */
    public static function findColumns($list = '*') :array
    {

        if (false === class_exists(self::class) || false === is_subclass_of(self::class, Model::class)) {
            return false;
        }

        $classColumns = array_keys((self::class)::getColumns());
        if (empty($list) || '*' == $list) {
            return $classColumns;
        }
        $list = is_array($list) ? $list : preg_split("~[\s,]~", $list, -1, PREG_SPLIT_NO_EMPTY);
        return array_intersect($classColumns, $list);
    }

}