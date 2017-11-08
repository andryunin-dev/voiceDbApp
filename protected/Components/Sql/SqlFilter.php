<?php
/**
 * Created by PhpStorm.
 * User: Dmitry
 * Date: 07.11.2017
 * Time: 14:08
 */

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
    }
    protected function validateColumn($column)
    {
        if (! $this->isColumnValid($column)) {
            throw new Exception('Property  ' . '\'' . $column . '\' is not found in class ' . $this->class);
        }
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
    public function addFilter(string $operator, string $column, array $values, $overwrite = false)
    {
        $this->validateOperator($operator);
        $this->validateColumn($column);

        $operator = strtolower($operator);
        //todo make check for unary operator
        if (empty($values)) {
            return $this;
        }

        if (! isset($this->filter->$operator)) {
            $this->filter->$operator = new Std([$column => $values]);
        } elseif (! isset($this->filter->$operator->$column)) {
            $this->filter->$operator->$column = new Std($values);
        } else {
            $this->filter->$operator->$column = (true === $overwrite) ?
                new Std($values) :
                new Std(array_merge($this->filter->$operator->$column->toArray(), array_diff($values, $this->filter->$operator->$column->toArray())));
        }
        return $this;
    }

    public function setFilter(string $operator, string $column, array $values)
    {
        $this->validateOperator($operator);
        $this->validateColumn($column);

        $this->addFilter($operator, $column, $values, true);
        return $this;
    }

    public function removeFilter(string $operator, string $column)
    {
        $this->validateOperator($operator);
        $this->validateColumn($column);
        unset($this->filter->$operator->$column);
        return $this;
    }

    /**
     * return all conditions as string for using in SQL query (like WHERE clause ie.)
     */
    protected function getFilterStatement()
    {
        $statementsByColumns = [];
        foreach ($this->filter as $op =>$cols) {
            foreach ($cols as $col => $vals) {
                $colStatement = [];
                foreach ($vals as $index => $val) {
                    $transRes = $this->sqlTranslator($op, $col, $index, $val);
                    $colStatement[] = $transRes['string'];
                    $this->params = array_merge($this->params, $transRes['param']);
                }
                $statementsByColumns[] = (count($colStatement) > 1) ?
                    '(' . implode(' OR ', $colStatement) . ')' :
                    array_pop($colStatement);
            }
        }
        $this->statementString = implode(' AND ', $statementsByColumns);
        return $this->statementString;
    }

    protected function sqlTranslator ($operator, $column, $index, $value)
    {
        $marker = ':' . $operator . '_' . $column . '_' . $index;
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
    {}

    public function toArray(): array
    {
        return $this->filter->toArray();
    }

    public function fromArray($dataSet) {

    }
}