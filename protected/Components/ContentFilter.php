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
use T4\Dbal\Connection;
use T4\Dbal\Query;

/**
 * Class ContentFilter
 * @package App\Components
 *
 * @property string $className
 * @property array $mappingArray
 * @property WhereStatement $whereStatement
 */
class ContentFilter extends Std
{
    const HREF_PROPERTY = 'href';
    const HREF_PROPERTY_EMPTY_VALUE = '';
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
        'like' => 'LIKE',
        'is' => 'IS'
    ];
    const PREDICATES_WITHOUT_PARAM = [
        'IS',
        'IS NOT'
    ];

    protected $className;
    protected $mappingArray;

    /**
     * Ищется колонка сначала в массиве маппинга, затем в свойствах класса $className
     * если найдена - возвращается ее имя, если нет - false
     * массив мапинга состоит из пары:
     *  альяс_колонки => имя_колонки
     *  или
     *  альяс_колонки => [COLUMN_NAME_KEY => имя_колонки, PREDICATE_NAME_KEY => предикат]
     * если найдено соответствие по 1-му варианту(без предиката), то предикат устанавливается 'eq'
     * если найдено соответствие по второму варианту - предикат берется из найденного массива по ключу PREDICATE_NAME_KEY
     *
     * @param string $column имя колонки для поиска
     * @return bool|array
     */
    protected function findColumn(string $column)
    {
        if (! class_exists($this->className) || ! is_array($this->mappingArray)) {
            return false;
        }
        //сначала ищем в массиве маппинга, потом в списке свойств класса, если нет - return false
        if (key_exists($column, $this->mappingArray)) {
            $column = $this->mappingArray[$column];
            if (is_string($column)) {
                $res[self::COLUMN_NAME_KEY] = $column;
                $res[self::PREDICATE_NAME_KEY] = 'eq';
            } elseif (is_array($column) && key_exists(self::COLUMN_NAME_KEY, $column) && key_exists(self::PREDICATE_NAME_KEY, $column)) {
                $res[self::COLUMN_NAME_KEY] = $column[self::COLUMN_NAME_KEY];
                $res[self::PREDICATE_NAME_KEY] = $column[self::PREDICATE_NAME_KEY];
            } else {
                $res = false;
            }
        } elseif (in_array($column, array_keys($this->className::getColumns()))) {
            $res[self::COLUMN_NAME_KEY] = $column;
            $res[self::PREDICATE_NAME_KEY] = 'eq';
        } else {
            $res = false;
        }
        return $res;
    }

    public function __construct($source = null, $className = '', array $mappingArray = [])
    {
        parent::__construct();
        $this->className = (empty($className) || !class_exists($className)) ? '' : $className;
        $this->mappingArray = $mappingArray;
        $isHrefFilter = false;

        if ($source instanceof Std) {
            $source = $source->toArrayRecursive();
        } elseif (! is_array($source)) {
            $source = [];
        }
        if (key_exists(self::HREF_PROPERTY, $source)) {
            $isHrefFilter = true;
        }
        if (true === $isHrefFilter && is_string($source[self::HREF_PROPERTY]) && ! empty($source[self::HREF_PROPERTY])) {
            $search = (new Url($source[self::HREF_PROPERTY]))->query;
            $search = (empty($search)) ?  [] : $search;
            unset($source[self::HREF_PROPERTY]);

            foreach ($search as $column => $value) {
                //map column to real property name of model
                //если имя column не может быть найдено в массиве мапинга или свойствах класса - отбрасываем его
                $columnSet = $this->findColumn($column);
                if (false === $column) {
                    continue;
                }
                $column = $columnSet[self::COLUMN_NAME_KEY];
                $statement = $columnSet[self::PREDICATE_NAME_KEY];
                //добавляем свойство $column
                $this
                    ->fill([
                        $column => new self()
                    ]);
                if (is_string($value)) {
                    $value = preg_split("/\s*,\s*/", $value, -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($value as $index => $itemValue) {
                        $value[$index] = is_numeric($itemValue) ? intval($itemValue) : $itemValue;
                    }
                } else {
                    continue;
                }
                if (empty($value)) {
                    unset($this->$column);
                    continue;
                }
                $this->$column
                    ->fill([$statement => $value]);
            }
            $this->{self::HREF_PROPERTY} = self::HREF_PROPERTY_EMPTY_VALUE; //если ставить null, то он не передается при следующем запросе
        } else {
            if (true === $isHrefFilter) {
                unset($source[self::HREF_PROPERTY]);
            }
            foreach ($source as $column => $statementSet) {
                //map column to real property name of model
                //если имя column не может быть найдено в массиве мапинга или свойствах класса - отбрасываем его
                $column = $this->findColumn($column);
                if (false === $column) {
                    continue;
                }
                //statement должен быть массивом
                if (! is_array($statementSet)) {
                    continue;
                }
                //добавляем свойство $column
                $this
                    ->fill([
                        $column[self::COLUMN_NAME_KEY] => new self()
                    ]);
                //анализируем $statementSet
                $statementRes = [];
                foreach ($statementSet as $statement => $value) {
                    if (! in_array(strtolower($statement), self::PREDICATES)) {
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
                $this->{$column[self::COLUMN_NAME_KEY]}->fill($statementRes);
            }
        }
    }

    public static function joinFilters(ContentFilter $mainFilter, ContentFilter $slaveFilter)
    {
        $target = new self();
        $target->merge($slaveFilter);
        $target->merge($mainFilter);
        if (isset($target->{self::HREF_PROPERTY})) {
            unset($target->{self::HREF_PROPERTY});
        }
        return $target;
    }

    public function mergeWith(ContentFilter $filter, $overwrite = true)
    {
        foreach ($filter as $column => $statements) {
            foreach ($statements as $predicate => $values) {
                if (isset($this->$column) && isset($this->$column->$predicate)) {
                    $this->$column->$predicate = $overwrite ? $values : array_unique(array_merge($this->$column->$predicate, $values));
                } else {
                    if (! isset($this->$column)) {
                        $this->$column = new self();
                    }
                    $this->$column->$predicate = $values;
                }
            }
        }
        return $this;
    }

    public function removeStatement(string $column, string $statement)
    {
        $column = $this->findColumn($column);
        $column = is_array($column) ? $column[self::COLUMN_NAME_KEY] : false;
        $statement = in_array(strtolower($statement), self::PREDICATES) ? strtolower($statement) : false;
        if (false === $column || false === $statement) {
            return $this;
        }
        if (isset($this->$column) && isset($this->$column->$statement)) {
            unset($this->$column->$statement);
        }
        return $this;
    }

    protected static function quoteName($column)
    {
//        $column = preg_split("/\s+/", $column, -1, PREG_SPLIT_NO_EMPTY);
//        if (count($column) > 1) {
//            $tail = array_pop($column);
//            if ('asc' == strtolower($tail) || 'desc' == strtolower($tail)) {
//                $column = implode(' ', $column);
//            } else {
//                $column = implode(' ', $column) . ' ' . $tail;
//            }
//        } else {
//            $column = array_pop($column);
//            $tail = '';
//        }
//        return '"' . $column . '"' . $tail;
        return '"' . $column . '"';
    }
    protected function getWhereStatement()
    {
        //собираем  WHERE statement
        $queryParams = [];
        $tableStatements = [];
        foreach ($this as $column => $conditions) {
            $columnStatement = [];
            foreach ($conditions as $predicate => $valuesItem) {
                foreach ($valuesItem as $index => $value) {
                    if (in_array(self::PREDICATE_REPLACEMENT[$predicate], self::PREDICATES_WITHOUT_PARAM)) {
                        $columnStatement[] = self::quoteName($column) . ' ' . self::PREDICATE_REPLACEMENT[$predicate] . ' ' . $value;
                    } else {
                        $realPredicate = self::PREDICATE_REPLACEMENT[$predicate];
                        $columnStatement[] = self::quoteName($column) . ' ' . $realPredicate . ' ' . ':' . $column . '_' . $predicate . '_' . $index;
                        $queryParams[':' . $column . '_' . $predicate . '_' . $index] = ('like' == strtolower($predicate)) ? $value . '%' : $value;
                    }
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
        $res = (new WhereStatement())
            ->fill([
                'where' => $whereStatement,
                'params' => $queryParams
            ]);
        return $res;
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
                    if (in_array(self::PREDICATE_REPLACEMENT[$predicate], self::PREDICATES_WITHOUT_PARAM)) {
                        $columnStatement[] = self::quoteName($column) . ' ' . self::PREDICATE_REPLACEMENT[$predicate] . ' ' . $value;
                    } else {
                        $realPredicate = self::PREDICATE_REPLACEMENT[$predicate];
                        $columnStatement[] = self::quoteName($column) . ' ' . $realPredicate . ' ' . ':' . $column . '_' . $predicate . '_' . $index;
                        $queryParams[':' . $column . '_' . $predicate . '_' . $index] = ('like' == strtolower($predicate)) ? $value . '%' : $value;
                    }
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
        $sorter = ($sorter instanceof Sorter) ? $sorter->sortBy : [];
        if (! empty($sorter)) {
            $query->order($sorter);
        }
        if (! empty($paginator) && $paginator->rowsOnPage > 0) {
            $query->offset(($paginator->page - 1) * $paginator->rowsOnPage);
            $query->limit($paginator->rowsOnPage);
        }
        return $query;
    }
    public function selectDistinctArrayByColumn(string $column, string $className,  array $mappingArray = [])
    {
        $column = $this->findColumn($column, $className, $mappingArray);
        if (empty($column)) {
            return false;
        }
        $column = $column[self::COLUMN_NAME_KEY];
        $query = $this->countQuery($className);
        $params = $query->params;

        $query = 'SELECT DISTINCT ' . $column .
            ' FROM ' . $className::getTableName() . ' WHERE ' . $query->where;
        $query = (new Query($query));
        $query->params = $params;
        $con = $className::getDbConnection();
        /**
         * @var Connection $stm
         */
        $res = $con->query($query);
        $res = $res->fetchAll(\PDO::FETCH_ASSOC);
        $res = array_map(function ($item) {
            return array_pop($item);
        }, $res);
        return $res;
    }
}