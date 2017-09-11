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


    /**
     * делает маппинг полей существующего фильтра через матрицу $columnMap
     * из переданного URL делаеот фильтр при наличии GET параметров
     * в фильтр добавляет св-ва whereClause и queryParams
     * возвращает отредактированный фильтр
     *
     * @param Std $tableFilter
     * @param Std $hrefFilter
     * @return Std
     */
    public static function buildFilter(Std $tableFilter, Std $hrefFilter)
    {
        //конвертирование tableFilter
        $convertedFilter = new Std();
        foreach ($tableFilter as $key => $value) {
            $column = self::findColumn($key);
            if ($column !== false) {
                $convertedFilter->$column = $value;
            }
        }
        $tableFilter = $convertedFilter;
        return self::joinFilters($tableFilter, $hrefFilter);
    }
    public static function buildTableFilter(Std $tableFilter)
    {
        //конвертирование tableFilter
        $convertedFilter = new Std();
        foreach ($tableFilter as $key => $value) {
            $column = self::findColumn($key);
            if ($column !== false) {
                $convertedFilter->$column = $value;
            }
        }
        $convertedFilter = $convertedFilter->toArrayRecursive();

        //форматируем фильтр
        foreach ($convertedFilter as $key => $filterItem) {
            if (is_array($filterItem)) {
                foreach ($filterItem as $clauseName => $clauseValue) {
                    if (is_string($clauseValue)) {
                        $asArray = preg_split("/\s*,\s*/", $clauseValue, -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($asArray as $index => $itemValue) {
                            $asArray[$index] = is_numeric($itemValue) ? intval($itemValue) : $itemValue;
                        }
                        $convertedFilter[$key][$clauseName] = $asArray;
                    } elseif (is_int($clauseValue)) {
                        $asArray = [];
                        $asArray[] = $filterItem[$clauseName];
                        $filterItem[$key][$clauseName] = $asArray;
                    }
                }
            }
        }
        return $convertedFilter;
    }

    public static function buildHrefFilter(Std $hrefFilter)
    {
        $convertedFilter = new Std();
        if (! isset($hrefFilter->href) || empty($hrefFilter->href)) {
            return $hrefFilter;
        }
        $search = (new Url($hrefFilter->href))->query;
        $search = (empty($search)) ?  [] : $search;
        foreach ($search as $key => $value) {
            $column = self::findColumn($key);
            if ($column !== false) {
                $convertedFilter->$column = (new Std(['eq' => $value]));
            }
        }
        $convertedFilter->href = null;
        //форматируем фильтр
        $convertedFilter = $convertedFilter->toArrayRecursive();
        foreach ($convertedFilter as $key => $filterItem) {
            if (is_array($filterItem)) {
                foreach ($filterItem as $clauseName => $clauseValue) {
                    if (is_string($clauseValue)) {
                        $asArray = preg_split("/\s*,\s*/", $clauseValue, -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($asArray as $index => $itemValue) {
                            $asArray[$index] = is_numeric($itemValue) ? intval($itemValue) : $itemValue;
                        }
                        $convertedFilter[$key][$clauseName] = $asArray;
                    } elseif (is_int($clauseValue)) {
                        $asArray = [];
                        $asArray[] = $filterItem[$clauseName];
                        $convertedFilter[$key][$clauseName] = $asArray;
                    }
                }
            }
        }

        return $convertedFilter;
    }

    public static function joinFilters($tableFilter, $hrefFilter)
    {
        $tableFilter = is_array($tableFilter) ? $tableFilter : ($tableFilter instanceof Std ? $tableFilter->toArrayRecursive() : []);
        $hrefFilter = is_array($hrefFilter) ? $hrefFilter : ($hrefFilter instanceof Std ? $hrefFilter->toArrayRecursive() : []);
        //join filters
        $joinedFilter = array_merge($hrefFilter, $tableFilter);
        //собираем  WHERE clause
        $queryParam = [];
        $tableClause = [];
        foreach ($joinedFilter as $column => $clause) {
            $columnClause = [];
            if (! is_array($clause)) {
                continue;
            }
            foreach ($clause as $clauseName => $clauseValue) {
                switch ($clauseName) {
                    case 'eq':
                        foreach ($clauseValue as $index => $value) {
                            $columnClause[] = self::quoteName($column) . ' = ' . ':' . $column . '_eq_' . $index;
                            $queryParam[':' . $column . '_eq_' . $index] = $value;
                        }
                        break;
                    case 'like':
                        foreach ($clauseValue as $index => $value) {
                            $columnClause[] = self::quoteName($column) . ' LIKE ' . ':' . $column . '_like_' . $index;
                            $queryParam[':' . $column . '_like_' . $index] = $value;
                        }
                        break;
                }
            }
            if (empty($columnClause)) {
                continue;
            }
            if (1 == count($columnClause)) {
                $tableClause[] = array_pop($columnClause);
            } else {
                $tableClause[] = '(' . implode(' OR ', $columnClause) . ')';
            }
        }
        $joinedFilter = new Std($joinedFilter);
        $joinedFilter->whereClause = implode(' AND ', $tableClause);
        $joinedFilter->queryParams = $queryParam;
        return $joinedFilter;
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

    /**
     * @param $filter
     * @param $sorting
     * @param $pager
     * @return Query $$query
     */
    public static function buildQuery($filter, $sorting, $pager)
    {
        $count = (new Query())
            ->select()
            ->from(self::getTableName());
        if (! empty($filter->whereClause)) {
            $count->where($filter->whereClause);
        }
//        $select = clone $count;
//
//        $sortingArray = preg_split("/\s*,\s*/", $sorting->sortBy, -1, PREG_SPLIT_NO_EMPTY);
//        $direction = $sorting->direction;
//        $sortingArray = array_map(function ($item) use ($direction) {
//            return $item . ' ' . $direction;
//        }, $sortingArray);
//        $select->order(implode(', ', $sortingArray))
//            ->offset($pager->rowsOnPage * ($pager->page - 1))
//            ->limit($pager->rowsOnPage);
//        $query = (new Std());
//        $query->count = $count;
//        $query->select = $select;
        return $count;
    }
    public static function updatePager(Std $pager)
    {
        $pager->rowsOnPage = (int)$pager->rowsOnPage;
        $pager->pages = ((int)$pager->rowsOnPage < 0) ? 1 : ceil($pager->records / (int)$pager->rowsOnPage);
        $pager->page = (int)$pager->page > $pager->pages ? 1 : (int)$pager->page;
        return $pager;
    }
}