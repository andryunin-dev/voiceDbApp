<?php


namespace App\Components;


use T4\Core\Std;

class SimpleTableHelpers
{
    protected static function sqWrapper($item)
    {
        return '\'' . $item .'\'';
    }
    protected static function dqWrapper($item)
    {
        return '"' . $item .'"';
    }
    protected static function convertToStd($value)
    {
        if (empty($value) || !(is_string($value) || $value instanceof Std)) {
            return false;
        }
        if (is_string($value) === true) {
            $value = json_decode($value);
            $value = new Std($value);
        }
        return $value;
    }
    /**
     * @param string|Std $filters can be encoded json string or decoded into Std object
     * @return string statement for WHERE clause
     */
    public static function filtersToStatement($filters)
    {
        $filters = self::convertToStd($filters);
        if ($filters === false)
        {
            return false;
        }

        $statements = [
            'IN_LIST' => '%s IN (%s)',
            'NOT_IN_LIST' => '%s NOT IN (%s)',
            'EQ' => '%s = \'%s\'',
            'NE' => '%s <> \'%s\'',
            'LT' => '%s < %s',
            'LE' => '%s <= %s',
            'GT' => '%s > %s',
            'GE' => '%s >= %s',
            'STARTING' => '%s LIKE \'%s%%\'',
            'ENDING' => '%s LIKE \'%%%s\'',
            'INCLUDING' => '%s LIKE \'%%%s%%\''
        ];
        $emptyStatements = [
            'ADD_EMPTY' => '%s IS NULL OR %s = \'\'',
            'REMOVE_EMPTY' => '%s IS NOT NULL AND %s <> \'\'',
        ];


        $resultStatements = [];

        foreach ($filters as $accessor => $filter) {
            $statementForAccessor = [];
            $filterBy = self::dqWrapper($filter->filterBy);
            $value = array_map(function($item) {return '\'' . $item .'\'';}, $filter->value->toArray());
            switch ($filter->type) {
                case 'IN_LIST':
                    if (count($value) === 0 && $filter->addEmpty === false) {
                        array_push($resultStatements, 'false');
                        break;
                    }
                    if (count($value) > 0)
                    {
                        array_push($statementForAccessor, sprintf($statements[$filter->type], $filterBy, implode(', ', $value)));
                    }
                    if (true ===$filter->addEmpty) {
                        array_push($statementForAccessor, sprintf($emptyStatements['ADD_EMPTY'], $filterBy, $filterBy));
                    }
                    array_push($resultStatements, implode(' OR ', $statementForAccessor));
                    break;
                case 'NOT_IN_LIST':
                    if (count($value) > 0)
                    {
                        array_push($statementForAccessor, sprintf($statements[$filter->type], $filterBy, implode(', ', $value)));
                    }
                    if (true ===$filter->removeEmpty) {
                        array_push($statementForAccessor, sprintf($emptyStatements['REMOVE_EMPTY'], $filterBy, $filterBy));
                    }
                    array_push($resultStatements, implode(' AND ', $statementForAccessor));
                    break;
                default:
                    $filterArray = $filter->value->toArray();
                    $filterValue = array_pop($filterArray);
                    array_push($resultStatements, sprintf($statements[$filter->type], $filter->filterBy, $filterValue));
            }
        }
        if (count($resultStatements) === 0) {
            return false;
        } elseif (count($resultStatements) === 1) {
            return array_pop($resultStatements);
        } else {
            $result = array_map(function($item) {return '(' . $item .')';}, $resultStatements);
            $resSt = implode(' AND ', $result);
            return $resSt;
        }
    }

    /**
     * @param string|Std $pagination can be encoded json string or decoded into Std object
     * @return string|false statement like OFFSET xx LIMIT xx or false
     */
    public static function paginationToOffsetLimitStatement($pagination)
    {
        $pagination = self::convertToStd($pagination);
        if ($pagination === false)
        {
            return false;
        }
        return is_int($pagination->offset) && is_int($pagination->limit)
            ? 'OFFSET ' . $pagination->offset . ' LIMIT ' . $pagination->limit
            : false;
    }
    /**
     * @param string|Std $pagination can be encoded json string or decoded into Std object
     * @return number|false value of offset or false
     */
    public static function paginationToOffsetValue($pagination)
    {
        $pagination = self::convertToStd($pagination);
        if ($pagination === false)
        {
            return false;
        }
        return is_int($pagination->offset)
            ? $pagination->offset
            : false;
    }
    /**
     * @param string|Std $pagination can be encoded json string or decoded into Std object
     * @return number|false value of limit or false
     */
    public static function paginationToLimitValue($pagination)
    {
        $pagination = self::convertToStd($pagination);
        if ($pagination === false)
        {
            return false;
        }
        return is_int($pagination->limit)
            ? $pagination->limit
            : false;
    }

    /**
     * @param string|Std $sorting can be encoded json string or decoded into Std object
     * @return string|false statement for ORDER BY
     */
    public static function sortingToStatement($sorting)
    {
        $sorting = self::convertToStd($sorting);
        if ($sorting === false)
        {
            return false;
        }
        $statement = [];
        $sorting = $sorting->toArray();
        foreach ($sorting as $key => $val) {
            $val = json_decode($val, true);
            $sortBy = self::dqWrapper(array_keys($val)[0]);
            $statement[] =  $sortBy . ' ' . array_values($val)[0];
        }
        return count($statement) === 0 ? false : implode(', ', $statement);
    }

}