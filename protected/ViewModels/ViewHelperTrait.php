<?php

namespace App\ViewModels;

use App\Components\SqlFilter;
use function foo\func;
use phpDocumentor\Reflection\Types\Array_;
use T4\Core\Std;
use T4\Core\Url;
use T4\Dbal\Query;
use T4\Http\Request;
use T4\Orm\Extensions\Standard;
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

    /**
     * ищет $name в свойствах класса либо в массиве мапинга
     * если не найдено - возвращает false
     * @param $name
     * @return bool
     */
    public static function findColumn($name)
    {
        $classColumns = array_keys((self::class)::getColumns());
        if (in_array($name, $classColumns)) {
            return $name;
        } elseif (isset(self::$columnMap)) {
            return key_exists($name, self::$columnMap) ? self::$columnMap[$name] : false;
        } else {
            return false;
        }
    }
    protected static function quoteColumnName($data)
    {
        return '"' . $data . '"';
    }
    public static function sortOrder($orderName = 'default')
    {
        return (array_key_exists($orderName, self::$sortOrders)) ? self::$sortOrders[$orderName] : self::$sortOrders['default'];
    }

    public static function officeIdListByQuery($query, string $office_idColumn)
    {
        $column = self::findColumn($office_idColumn);
        if (false === $column) {
            return false;
        }
        $select = 'SELECT DISTINCT ' . self::quoteColumnName($column) . ' FROM ' . DevModulePortGeo::getTableName();
        if (! empty($query->where)) {
            $select .= ' WHERE ' . $query->where;
        }
        $param = isset($query->params) ? $query->params : [];
        $query = (new Query($select))->params($param);
        $lotusIdArray = self::getDbConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        $lotusIdArray = array_map(function ($item) use ($column) {
            return $item[$column];
        }, $lotusIdArray);
        return $lotusIdArray;
    }
}