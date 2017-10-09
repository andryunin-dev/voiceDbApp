<?php
/**
 * Created by PhpStorm.
 * User: karasev-dl
 * Date: 12.09.2017
 * Time: 15:10
 */

namespace App\Components;


use T4\Core\Std;

/**
 * Class Sorter
 * @package App\Components
 *
 * @property array $sortBy
 * @property string $direction ASC or DESC
 */
class Sorter extends Std
{
    const COLUMN_NAME_KEY = 'column';

    protected $className;
    protected $mappingArray;

    public function __construct($columns = [], string $direction = '', string $className,  array $mappingArray = [])
    {
        parent::__construct();
        $this->className = (empty($className) || !class_exists($className)) ? '' : $className;
        $this->mappingArray = $mappingArray;

        if (empty($columns)) {
            $this->fill(['sortBy' => []]);
        } elseif (is_string($columns)) {
            $columns = preg_split("/\s*,\s*/", $columns, -1, PREG_SPLIT_NO_EMPTY);
            $columns = array_map([$this, 'findColumn'], $columns);
            $this->fill(['sortBy' => $columns]);
        } elseif (is_array($columns)) {
            $columns = array_map([$this, 'findColumn'], $columns);
            $this->fill(['sortBy' => $columns]);
        } else {
            $this->fill(['sortBy' => []]);
        }
        if (! empty($this->sortBy)) {
            $direction = ('asc' == strtolower($direction) || 'desc' == strtolower($direction)) ? strtoupper($direction): '';
            $this->fill(['direction' => $direction]);
            $direction = empty($direction) ? '' : ' ' . $direction;
            $this->sortBy = array_map(function ($column) use ($direction) {
                $column = preg_split("/\s+/", $column, -1, PREG_SPLIT_NO_EMPTY);
                if (count($column) > 1) {
                    $tail = array_pop($column);
                    if ('asc' == strtolower($tail) || 'desc' == strtolower($tail)) {
                        $column = implode(' ', $column);
                    } else {
                        $column = implode(' ', $column) . ' ' . $tail;
                    }
                } else {
                    $column = array_pop($column);
                    $tail = '';
                }
                if (empty($tail)) {
                    return self::quoteName($column) . $direction;
                } else {
                    return self::quoteName($column) . ' ' . $tail;
                }
            }, $this->sortBy);
        }
    }

    public function __toString()
    {
        $direction = $this->direction;
        $tmpArray = array_map(function ($item) use ($direction) {
            return self::quoteName($item) . ' ' . $direction;
        }, $this->sortBy->toArrayRecursive());
        return implode(', ', $tmpArray);
    }

    protected static function quoteName($column)
    {
        return '"' . $column . '"';
    }

    protected function findColumn(string $column)
    {
        if (! class_exists($this->className) || ! is_array($this->mappingArray)) {
            return false;
        }
        // отрезаем перед поиском 'asc' 'desc'
        $column = preg_split("/\s+/", $column, -1, PREG_SPLIT_NO_EMPTY);
        if (count($column) > 1) {
            $tail = array_pop($column);
            if ('asc' == strtolower($tail) || 'desc' == strtolower($tail)) {
                $column = implode(' ', $column);
            } else {
                $column = implode(' ', $column) . ' ' . $tail;
            }
        } else {
            $column = array_pop($column);
            $tail = '';
        }

        //сначала ищем в массиве маппинга, потом в списке свойств класса, если нет - return false
        if (key_exists($column, $this->mappingArray)) {
            $column = $this->mappingArray[$column];
            if (is_array($column) && key_exists(self::COLUMN_NAME_KEY, $column)) {
                $res = $column[self::COLUMN_NAME_KEY];
            } elseif (is_string($column)) {
                $res = $column;
            } else {
                $res = false;
            }
        } elseif (in_array($column, array_keys($this->className::getColumns()))) {
            $res = $column;
        } else {
            $res = false;
        }
        $tail = empty($tail) ? $tail : ' ' . $tail;
        return $res === false ? false : $res . strtoupper($tail);
    }

}