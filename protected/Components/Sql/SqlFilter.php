<?php

namespace App\Components\Sql;

use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\IDriver;
use T4\Orm\Model;

/**
 * Class SqlFilter
 * @package App\Components\Sql
 *
 * @property string $filterStatement
 * @property array $filterParams
 */
class SqlFilter extends Std
{
    protected static $operatorsToSings = [
        'eq' => '=',
        'ne' => '!=',
        'lt' => '<',
        'le' => '<=',
        'gt' => '>',
        'ge' => '>=',
        'isnull' => 'isnull',
        'notnull' => 'notnull'

    ];

    protected static $unaryOperators = [
        'isnull',
        'notnull'
    ];

    protected $class;
    /**
     * @var IDriver $driver
     */
    protected $driver;
    protected $filter;
    protected $statementString = '';
    protected $params = [];

    public function __construct(string $class)
    {
        if (! class_exists($class)) {
            throw new Exception('Class ' . $class . ' is not exists');
        }
        if (get_parent_class($class) != Model::class) {
            throw new Exception('Class for SqlFilter must extends Model class');
        }
        parent::__construct();
        $this->class = $class;
        $this->driver = $class::getDbDriver();
        $this->filter = new Std();
    }

    protected function isFilterExists($operator, $column)
    {
        return isset($this->filter->$operator->$column);
    }
    protected function isOperatorUnary($operator)
    {
        return in_array($operator, self::$unaryOperators);
    }
    protected function isOperatorValid($operator)
    {
        return array_key_exists(strtolower($operator), self::$operatorsToSings);
    }
    protected function isColumnValid($column)
    {
        return array_key_exists($column, $this->class::getColumns());
    }
    protected function validateOperator($operator)
    {
        if (! $this->isOperatorValid($operator)) {
            throw new Exception('Unknown operator - ' . $operator);
        }
        return true;
    }
    protected function validateColumn($column)
    {
        if (! $this->isColumnValid($column)) {
            throw new Exception('Property  ' . '\'' . $column . '\' is not found in class ' . $this->class);
        }
        return true;
    }

    /**
     * if set protected = true this
     *
     * @param string $operator
     * @param string $column
     * @param array $values
     * @param bool $overwrite
     * @return $this
     * @throws Exception
     */
    public function addFilter(string $column, string $operator, array $values, $overwrite = false)
    {
        $this->validateColumn($column);
        $this->validateOperator($operator);

        $operator = strtolower($operator);
        //todo make check for unary operator
        if (empty($values)) {
            return $this;
        }

        if (! isset($this->filter->$column)) {
            $this->filter->$column = new Std([$operator => $values]);
        } elseif (! isset($this->filter->$column->$operator)) {
            $this->filter->$column->$operator = new Std($values);
        } else {
            $this->filter->$column->$operator = (true === $overwrite) ?
                new Std($values) :
                new Std(array_merge($this->filter->$column->$operator->toArray(), array_diff($values, $this->filter->$column->$operator->toArray())));
        }
        return $this;
    }

    public function setFilter(string $column, string $operator, array $values)
    {
        $this->validateColumn($column);
        $this->validateOperator($operator);

        $this->addFilter($column, $operator, $values, true);
        return $this;
    }

    public function removeFilter(string $column, string $operator)
    {
        $this->validateColumn($column);
        $this->validateOperator($operator);
        unset($this->filter->$column->$operator);
        if (0 == $this->filter->$column->count()) {
            unset($this->filter->$column);
        }
        return $this;
    }

    /**
     * return all conditions as string for using in SQL query (like WHERE clause ie.)
     */
    protected function getFilterStatement()
    {
        $statementsByColumns = [];
        foreach ($this->filter as $col =>$ops) {
            foreach ($ops as $op => $vals) {
                $opStatement = [];
                foreach ($vals as $index => $val) {
                    $transRes = $this->sqlTranslator($col, $op, $index, $val);
                    $opStatement[] = $transRes['string'];
                    $this->params = array_merge($this->params, $transRes['param']);
                }
                $statementsByColumns[] = (count($opStatement) > 1) ?
                    '(' . implode(' OR ', $opStatement) . ')' :
                    array_pop($opStatement);
            }
        }
        $this->statementString = implode(' AND ', $statementsByColumns);
        return $this->statementString;
    }

    protected function sqlTranslator ($column, $operator, $index, $value)
    {
        $marker = ':' . $column . '_' . $operator . '_' . $index;
        $param = [];
        if ($this->isOperatorUnary($operator)) {
            $string = $this->driver->quoteName($column) . ' ' . strtoupper(self::$operatorsToSings[$operator]);
        } else {
            $string = $this->driver->quoteName($column) . ' ' . strtoupper(self::$operatorsToSings[$operator]) . ' ' . $marker;
            $param[$marker] = $value;
        }
        return [
            'string' => $string,
            'param' => $param
        ];
    }

    /**
     * return parameters array for filter statement
     */
    protected function getFilterParams()
    {
        $params = $this->params;
        $this->params = [];
        return $params;
    }

    /**
     * merge current filter with filter passed as argument.
     * variable $merge mode define behavior merge process
     * mode 'replace' - if operator and column match with corresponding current filter fields,  new values replace current
     * mode 'append' - if operator and column match with corresponding current filter fields, new values append to currents
     * mode 'ignore' - if operator and column match with corresponding current filter fields, new values will be ignored
     *
     * @param SqlFilter $filter
     * @param string $mergeMode
     */
    public function mergeWith(SqlFilter $filter, string $mergeMode = 'replace')
    {
        $overwrite = 'replace' == $mergeMode ? true : false;
        $ignore = ('ignore' == $mergeMode) ? true : false;
        $filter = $filter->toArray();
        foreach ($filter as $col => $ops) {
            foreach ($ops as $op => $val) {
                if (! isset($this->filter->$col->$op)) {
                    $this->addFilter($col, $op, $val, true);
                } elseif (isset($this->filter->$col->$op) && (true === $ignore)) {
                    continue;
                } else {
                    $this->addFilter($col, $op, $val, $overwrite);
                }
            }
        }
    }

    public function toArray(): array
    {
        return $this->filter->toArray();
    }

    public function setFilterFromArray($data)
    {
        foreach ($data as $col => $ops) {
            $this->validateColumn($col);
            foreach ($ops as $op => $vals) {
                $this->validateOperator($op);
                if (! is_array($vals)) {
                    throw new Exception('Values have to pass as array');
                }
                $this->setFilter($col, $op, $vals);
            }
        }
        return $this->filter;
    }
    public function addFilterFromArray($data, $overwrite = false)
    {
        foreach ($data as $col => $ops) {
            $this->validateColumn($col);
            foreach ($ops as $op => $vals) {
                $this->validateOperator($op);
                if (! is_array($vals)) {
                    throw new Exception('Values have to pass as array');
                }
                $this->addFilter($col, $op, $vals, $overwrite);
            }
        }
        return $this->filter;
    }
}