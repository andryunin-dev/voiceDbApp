<?php
/**
 * Created by PhpStorm.
 * User: karasev-dl
 * Date: 12.09.2017
 * Time: 9:28
 */

namespace App\Components;


use T4\Core\Std;

class TableFilter extends Std
{
    const COLUMN_NAME_KEY = 'column';
    const PREDICATE_NAME_KEY = 'predicate';
    const PREDICATES = ['eq', 'ne', 'lt', 'le', 'gt', 'ge', 'like'];

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

    public function __construct($source = null, string $className = null, array $mappingArray = [])
    {
        $data = [];
        if ($source instanceof Std) {
            $source = $source->toArrayRecursive();
        } elseif (! is_array($source)) {
            $source = [];
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
    }
}