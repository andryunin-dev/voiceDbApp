<?php

namespace App\ViewModels;

use App\Components\SqlFilter;
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
    public static function findColumn($columnName)
    {
        $classColumns = array_keys((self::class)::getColumns());
        return array_search($columnName, $classColumns);
    }


    /**
     * делает маппинг полей существующего фильтра через матрицу $columnMap
     * из переданного URL делаеот фильтр при наличии GET параметров
     * в фильтр добавляет св-ва whereClause и queryParams
     * возвращает отредактированный фильтр
     *
     * @param Std $filter
     * @param string $href
     * @return Std
     */
    public static function buildFilter(Std $filter, string $href)
    {
        $resFilter = new Std();

        foreach ($filter as $key => $value) {
            if (key_exists($key, self::$columnMap)) {
                $resFilter->{self::$columnMap[$key]} = $value;
            } elseif (false !== key_exists($key, (self::class)::getColumns())) {
                $resFilter->$key = $value;
            }
        }
        //делаем фильтр из GET параметров
        $search = (new Url($href))->query;
        foreach ($search as $key => $value) {
            $value = is_numeric($value) ? intval($value) : $value;
            //todo вставить поиск по массиву свойств модели!!!
            $column = self::findColumn($key);
            if ($column !== false) {
                $resFilter->$column = (new Std(['eq' => $value]));
            } elseif (key_exists($key, self::$columnMap)) {
                $resFilter->{self::$columnMap[$key]} = (new Std(['eq' => $value]));
            } elseif (false !== array_search($key, self::$columnMap)) {
                $resFilter->$key = (new Std(['eq' => $value]));
            }
        }
        self::joinFilter($resFilter);
        return $resFilter;
    }

    public static function joinFilter(Std $filter)
    {
        $res = [];
        $params = [];
        foreach ($filter as $key => $filterItem) {
            switch (true) {
                case isset($filterItem->eq):
                    if (is_string($filterItem->eq)) {
                        $eqArray = preg_split("/\s*,\s*/", $filterItem->eq, -1, PREG_SPLIT_NO_EMPTY);
                        $filterItem->eq = $eqArray;
                    } elseif (is_int($filterItem->eq)) {
                        $eqArray = [];
                        $eqArray[] = $filterItem->eq;
                        $filterItem->eq = $eqArray;
                    }
                    $subRes = [];
                    foreach ($filterItem->eq as $index => $value) {
                        $subRes[] = self::quoteName($key) . ' = ' . ':' . $key . $index;
                        $params[':' . $key . $index] = $value;
                    }
                    if (count($subRes) == 1) {
                        $res[] = $subRes[0];
                    } elseif (count($subRes) > 1) {
                        $res[] = '(' . implode(' OR ', $subRes) . ')';
                    }
                    break;
                case isset($key->like):
                    $res[] = self::quoteName($key) . ' LIKE ' . ':' . $key;
                    $params[':' . $key] = $filterItem->like;
                    break;
            }
        }
        $filter->whereClause = implode(' AND ', $res);
        $filter->queryParams = $params;
        return $filter;
    }

    protected static function quoteName($data)
    {
        return '"' . $data . '"';
    }

    public static function buildSorting(Std $sorting)
    {
        if ($sorting->direction != 'asc' && $sorting->direction != 'desc') {
            $sorting->direction = 'DESC';
        } else {
            $sorting->direction = strtoupper($sorting->direction);
        }
        $sorting->sortBy = self::sortOrder($sorting->sortBy);
        return $sorting;
    }
    public static function sortOrder($orderName = 'default')
    {
        return (array_key_exists($orderName, self::$sortOrders)) ? self::$sortOrders[$orderName] : self::$sortOrders['default'];
    }

    public static function buildQuery($filter, $sorting, $pager)
    {
        $query = (new Query())
            ->select()
            ->from(self::getTableName());
        if (! empty($filter->whereClause)) {
            $query->where($filter->whereClause);
        }
        return $query;
    }
}