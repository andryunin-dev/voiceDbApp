<?php
/**
 * Created by IntelliJ IDEA.
 * User: karasev-dl
 * Date: 08.11.2018
 * Time: 12:01
 */

namespace App\Components\Sql;


use phpDocumentor\Reflection\Types\String_;
use T4\Core\Std;
use T4\Dbal\QueryBuilder;

/**
 * Class SqlSearcher
 * @package App\Components\Sql
 * @property string $expression
 * @property array $parameters
 */
class SqlSearcher extends Std
{
    /**
     * @var Std
     */
    protected $filters;
    
    /**
     * @var array $conditionals
     * array for mapping input operators to SQL operators
     * 'operator' - appropriated SQL operator
     * 'paramTemplate' - use to form correct parameter for this SQL operator
     * 'castTo' - what type will cast to all accessor's fields
     */
    protected $conditionals = [
        'beginWith' => [
            'operator' => 'LIKE',
            'paramTemplate' => '%s%%',
            'castAccessorTo' => 'citext'
        ]
    ];
    
    public function __construct(array $filters = [])
    {
        parent::__construct();
        $this->filters = new Std($filters);
        $this->buildConditional();
    }
    
    /**
     * @param string $filterStatement
     * @param string $accessor
     * @return string
     */
    protected function castAccessorFiled($filterStatement, $accessor)
    {
        if (empty($this->conditionals[$filterStatement]['castAccessorTo'])) {
            return $accessor;
        }
        return sprintf('"%s"::%s', $accessor, $this->conditionals[$filterStatement]['castAccessorTo']);
    }
    
    protected function buildConditional()
    {
        foreach ($this->filters as $index => $filter) {
            $expressions = [];
            $parameters = [];
            if ($filter->value && !empty($filter->value)) {
                $paramName = ':f' . $index;
                foreach ($filter->searchBy as $accessor) {
                    $operator = $this->conditionals[$filter->statement]['operator'];
//                    $paramName = ':' . $index . '_' . $accessor;
                    $expressions[] = sprintf('%s %s %s' , $this->castAccessorFiled($filter->statement, $accessor), $operator, $paramName );
                    $parameters[$paramName] = sprintf($this->conditionals[$filter->statement]['paramTemplate'], $filter->value);
                }
                $filter->expressions = isset($expressions) ? $expressions : [];
                $filter->parameters = isset($parameters) ? $parameters : [];
            }
        }
        $expressions = [];
        $parameters = [];
        foreach ($this->filters as $filter) {
            if ($filter->expressions && $filter->parameters) {
                $expressions[] = implode(' OR ', $filter->expressions);
                $parameters = array_merge($parameters, $filter->parameters);
            }
        }
        if (count($expressions) >1) {
            foreach ($expressions as $index => $expression) {
                $expressions[$index] = sprintf('(%s)', $expression);
            }
        }
        $this->filters->expression = implode(' AND ', $expressions);
        $this->filters->parameters = $parameters;
    }
    
    protected function getExpression()
    {
        return $this->filters->expression;
    }
    
    protected function getParameters()
    {
        return $this->filters->parameters;
    }
}