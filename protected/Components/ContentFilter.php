<?php
/**
 * Created by PhpStorm.
 * User: karasev-dl
 * Date: 12.09.2017
 * Time: 12:57
 */

namespace App\Components;


use T4\Core\Std;
use T4\Core\Url;
use T4\Dbal\Query;

class ContentFilter extends Std
{
    const HREF_PROPERTY = 'href';
    const COLUMN_NAME_KEY = 'column';
    const PREDICATE_NAME_KEY = 'predicate';
    const PREDICATES = ['eq', 'ne', 'lt', 'le', 'gt', 'ge', 'like'];
    const PREDICATE_REPLACEMENT = [
        'eq' => '=',
        'ne' => '!=',
        'lt' => '<',
        'le' => '<=',
        'gt' => '>',
        'ge' => '>=',
        'like' => 'LIKE'
    ];

    /**
     * @param string $column
     * @param string $className
     * @param array $mappingArray
     * @return bool|array
     */
    protected static function findColumn(string $column, string $className, array $mappingArray = [])
    {
        if (! class_exists($className) || ! is_array($mappingArray)) {
            return false;
        }
        //сначала ищем в массиве маппинга, потом в списке свойств класса, если нет - return false
        if (key_exists($column, $mappingArray)) {
            $column = $mappingArray[$column];
            if (is_string($column)) {
                $res[self::COLUMN_NAME_KEY] = $column;
                $res[self::PREDICATE_NAME_KEY] = 'eq';
            } elseif (is_array($column) && key_exists(self::COLUMN_NAME_KEY, $column) && key_exists(self::PREDICATE_NAME_KEY, $column)) {
                $res[self::COLUMN_NAME_KEY] = $column[self::COLUMN_NAME_KEY];
                $res[self::PREDICATE_NAME_KEY] = $column[self::PREDICATE_NAME_KEY];
            } else {
                $res = false;
            }
        } elseif (in_array($column, array_keys($className::getColumns()))) {
            $res[self::COLUMN_NAME_KEY] = $column;
            $res[self::PREDICATE_NAME_KEY] = 'eq';
        } else {
            $res = false;
        }
        return $res;
    }

    public function __construct($source = null, $className = null, array $mappingArray = [])
    {
        $data = [];
        $isHrefFilter = false;

        if ($source instanceof Std) {
            $source = $source->toArrayRecursive();
        } elseif (! is_array($source)) {
            $source = [];
        }
        if (key_exists(self::HREF_PROPERTY, $source)) {
            $isHrefFilter = true;
        }
        if (true === $isHrefFilter && is_string($source[self::HREF_PROPERTY])) {
            $search = (new Url($source[self::HREF_PROPERTY]))->query;
            $search = (empty($search)) ?  [] : $search;
            unset($source[self::HREF_PROPERTY]);
            foreach ($search as $column => $value) {
                $findResult = self::findColumn($column, $className, $mappingArray);
                if (false === $findResult) {
                    continue;
                } else {
                    $column = $findResult[self::COLUMN_NAME_KEY];
                    $predicate = $findResult[self::PREDICATE_NAME_KEY];
                    if (is_string($value)) {
                        $asArray = preg_split("/\s*,\s*/", $value, -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($asArray as $index => $itemValue) {
                            $asArray[$index] = is_numeric($itemValue) ? intval($itemValue) : $itemValue;
                        }
                        $value = $asArray;
                    } else {
                        continue;
                    }
                }
                $data[$column][$predicate] = $value;
            }
            parent::__construct($data);
            $this->{self::HREF_PROPERTY} = null;
        } else {
            if (true === $isHrefFilter) {
                unset($source[self::HREF_PROPERTY]);
            }
            foreach ($source as $column => $item) {
                $column = self::findColumn($column, $className, $mappingArray);
                if (false === $column) {
                    continue;
                } else {
                    $column = $column[self::COLUMN_NAME_KEY];
                }
                if (! is_array($item)) {
                    continue;
                }
                foreach ($item as $predicate => $value) {
                    if (in_array($predicate, self::PREDICATES) && (is_string($value) || is_numeric($value))) {
                        if (is_string($value)) {
                            $asArray = preg_split("/\s*,\s*/", $value, -1, PREG_SPLIT_NO_EMPTY);
                            foreach ($asArray as $index => $itemValue) {
                                $asArray[$index] = is_numeric($itemValue) ? intval($itemValue) : $itemValue;
                            }
                            $value = $asArray;
                        } elseif (is_int($value)) {
                            $value = array($value);
                        }
                        $data[$column][$predicate] = $value;
                    }
                }
            }
            parent::__construct($data);
            if (true === $isHrefFilter) {
                $this->{self::HREF_PROPERTY} = null;
            }
        }
    }

    public static function joinFilters(ContentFilter $targetFilter, ContentFilter $appendedFilter)
    {
        return $appendedFilter->merge($targetFilter);
    }

    protected static function quoteName($data)
    {
        return '"' . $data . '"';
    }

    public function countQuery(string $className)
    {
        //собираем  WHERE clause
        $queryParams = [];
        $tableStatements = [];
        foreach ($this as $column => $conditions) {
            $columnStatement = [];
            foreach ($conditions as $predicate => $valuesItem) {
                foreach ($valuesItem as $index => $value) {
                    $columnStatement[] = self::quoteName($column) . ' ' . self::PREDICATE_REPLACEMENT[$predicate] . ' ' . ':' . $column . '_' . $predicate . '_' . $index;
                    $queryParams[':' . $column . '_' . $predicate . '_' . $index] = $value;
                }
            }
            if (empty($columnStatement)) {
                continue;
            }
            if (1 == count($columnStatement)) {
                $tableStatements[] = array_pop($columnStatement);
            } else {
                $tableStatements[] = '(' . implode(' OR ', $columnStatement) . ')';
            }
        }
        $whereStatement = implode(' AND ', $tableStatements);
        $query = (new Query())
            ->select()
            ->from($className::getTableName());
        if (! empty($whereStatement)) {
            $query
                ->where($whereStatement)
                ->params($queryParams);
        }
        return $query;
    }
    public function selectQuery(string $className, Sorter $sorter = null,  Paginator $paginator = null)
    {
        $query = $this->countQuery($className);
        if (! empty($sorter)) {
            $query->order((string)$sorter);
        }
        if (! empty($paginator)) {

        }
    }
}