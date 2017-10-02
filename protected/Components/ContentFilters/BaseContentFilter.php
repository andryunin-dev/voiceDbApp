<?php

namespace App\Components\ContentFilters;

use T4\Core\Std;

abstract class BaseContentFilter extends Std
{
    const COLUMN_NAME_KEY = 'column';
    const STATEMENT_NAME_KEY = 'statement';
    const STATEMENTS = ['eq', 'ne', 'lt', 'le', 'gt', 'ge', 'like']; //массив валидных операторов сравния

    /**
     * Ищется колонка сначала в массиве маппинга, затем в свойствах класса $className
     * если найдена - возвращается ее имя, если нет - false
     * массив мапинга состоит из пары:
     *  'альяс_колонки' => 'имя_колонки'
     *  или
     *  'альяс_колонки' => [
     *                          COLUMN_NAME_KEY => 'имя_колонки',
     *                          STATEMENT_NAME_KEY => 'оператор сравнения'
     *                     ]
     * если найдено соответствие по 1-му варианту(без предиката), то возвращаем 'имя_колонки"
     * если найдено соответствие по второму варианту - значение массива по ключу COLUMN_NAME_KEY
     *
     * @param string $column имя колонки для поиска
     * @param string $className имя класса для которого ищется колонка
     * @param array $mappingArray массив для мапинга
     * @return bool|array
     */
    protected static function findColumn(string $column, string $className = '', array $mappingArray = [])
    {
        if (! class_exists($className) || ! is_array($mappingArray)) {
            return false;
        }
        //сначала ищем в массиве маппинга, потом в списке свойств класса, если нет - return false
        if (key_exists($column, $mappingArray)) {
            $column = $mappingArray[$column];
            if (is_string($column)) {
                return $column;
            }
            if (is_array($column) && key_exists(self::COLUMN_NAME_KEY, $column)) {
                return $column[self::COLUMN_NAME_KEY];
            } else {
                return false;
            }
        } elseif (in_array($column, array_keys($className::getColumns()))) {
            return $column;
        } else {
            return false;
        }
    }

    /**
     * BaseContentFilter constructor.
     * @param null $data
     * @param string $className
     * @param array $mappingArray
     *
     * формат входных данных (на примере объекта Std):
     * 'columnName_1'->'statement'->'value'
     * 'columnName_2'->'statement'->'value'
     * формат входных данных (на примере массива):
     * [
     *      'columnName_1' => ['statement' => 'value'],
     *      'columnName_2' => ['statement' => 'value']
     * ]
     * условий сравнения для каждой колонки может быть несколько, например:
     * на примере объекта Std:
     * 'columnName_1'->'statement_1'->'value'
     *               ->'statement_2'->'value'
     * на примере массива:
     * [
     *      'columnName_1' => [
     *                          'statement_1' => 'value',
     *                          'statement_2' => 'value'
     *                        ],
     *      'columnName_2' => [
     *                          'statement_1' => 'value',
     *                          'statement_2' => 'value'
     *                        ],
     * ]
     * 'value' может быть строкой или массивом.
     * если 'value' строка - то она может содержать несколько значений разделенных запятыми
     * если 'value' массив - то может содержать одно или несколько значений. Массив воспринимается как цифровой (ключи массива игнорируются)
     */
    public function __construct($data = null, $className = '', array $mappingArray = [])
    {
        //convert data to array if it possible
        if ($data instanceof Std) {
            $data = $data->toArrayRecursive();
        } elseif (! is_array($data)) {
            $data = [];
        }
        parent::__construct();
        //validate and sanitize $data
        foreach ($data as $column => $statementSet) {
            //map column to real property name of model
            //если имя column не может быть найдено в массиве мапинга или свойствах класса - отбрасываем его
            $column = self::findColumn($column, $className, $mappingArray);
            if (false === $column) {
                continue;
            }
            //statement должен быть массивом
            if (! is_array($statementSet)) {
                continue;
            }
            //добавляем свойство $column
            $this->$column = new static;
            //анализируем $statementSet
            $statementRes = [];
            foreach ($statementSet as $statement => $value) {
                if (! in_array(strtolower($statement), self::STATEMENTS)) {
                    continue;
                }
                $statement = strtolower($statement);
                if (is_string($value)) {
                    $value = preg_split("/\s*,\s*/", $value, -1, PREG_SPLIT_NO_EMPTY);
                } elseif (is_array($value)) {
                    $value = array_values($value);
                } else {
                    continue;
                }
                if (empty($value)) {
                    continue;
                }
                $statementRes[$statement] = $value;
            }
            if (empty($statementRes)) {
                unset($this->$column);
                continue;
            }
            $this->$column->fill($statementRes);
        }
    }

    public function appendStatement($newStatement)
    {


    }
}