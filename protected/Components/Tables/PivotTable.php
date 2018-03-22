<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\Query;

/**
 * Class PivotTable
 * @package App\Components\Tables
 *
 * @property PivotTableConfig $config
 */
class PivotTable extends Table implements PivotTableInterface
{
    protected $pivotItemProperties = [
        'isPivot' => true,
        'pivotColumnAlias' => ''
    ];

    public function __construct(PivotTableConfig $tableConfig)
    {
        parent::__construct($tableConfig);
    }

    public function findPivotItems(string $pivotAlias)
    {
        if (false === $this->config->isPivot($pivotAlias)) {
            return false;
        }
        $filter = $this->config->pivotPreFilter($pivotAlias)->mergeWith($this->filter, 'ignore');
        $pivColumn = $this->config->pivotColumnByAlias($pivotAlias);
        $filterStatement = $filter->filterStatement;
        $filterParams = $filter->filterParams;
        $sortBy = $this->config->pivotSortByQuotedString($pivotAlias);
        $query = (new Query())
            ->select($pivColumn->column)
            ->distinct()
            ->from($this->config->className()::getTableName());
        if (!empty($filterStatement)) {
            $query
                ->where($filterStatement);
        }
        if (!empty($sortBy)) {
            $query->order($sortBy);
        }
        $query = $this->driver->makeQueryString($query);
        $queryRes = $this->config->className::getDbConnection()->query($query, $filterParams)->fetchAll(\PDO::FETCH_COLUMN, 0);
        return new Std($queryRes);
    }

    public function buildTableConfig(): Std
    {
        /*build columns config*/
        $pivots = $this->config->pivots();
        $columnsConf = new Std();
        $pivotWidth = 0;
        foreach ($this->config->columns as $col => $colConf) {
            if (! $this->config->isColumnVisible($col)) {
                continue;
            }
            if (! $this->config->isPivot($col)) {
                $columnsConf->$col = $colConf;
                continue;
            }
            $pivotWidth += $this->config->columnConfig($col)->width;
            $pivotItems = $this->findPivotItems($col);
            $propsTemplate = $this->config->columnPropertiesTemplate->merge(new Std($this->pivotItemProperties));
            foreach ($pivotItems as $idx => $item) {
                $columnsConf->$item = new Std($propsTemplate->toArray());
                $columnsConf->$item->pivotColumn = $col;
                $columnsConf->$item->id = $col . '_' . $idx;
                $columnsConf->$item->name = $item;
                $columnsConf->$item->width = $this->config->pivotWidthItems($col);
                $columnsConf->$item->classes = $colConf->classes;
            }
        }

        /*build table config*/
        $tbConf = new Std();
        $tbConf->dataUrl = $this->config->dataUrl();
        $tbConf->width = $this->config->tableWidth();

        $tbConf->header = new Std();
        $tbConf->header->tableClasses = implode(', ', $this->config->headerCssClasses->toArray());
        $tbConf->header->pivotColumnsWidth = $pivotWidth;
        $tbConf->header->columns = $columnsConf;

        $tbConf->pager = new Std(
            [
                'rowsOnPage' => $this->rowsOnPage(),
                'rowList' => $this->config->rowsOnPageList()->toArray()
            ]
        );
        $tbConf->styles = new Std(
            [
                'header' => [
                    'table' => [
                        'classes' => [],
                    ]
                ],
                'body' => [
                    'table' => [
                        'classes' => [],
                    ]
                ],
                'bodyFooter' => [
                    'table' => [
                        'classes' => [],
                    ]
                ],
            ]
        );
        $tbConf->styles->header->table->classes = $this->config->headerCssClasses->table;
        $tbConf->styles->body->table->classes = $this->config->bodyCssClasses->table;

        $bodyFooterTable = $this->config->bodyFooterTableName();
        if (! empty($bodyFooterTable)) {
            $tbConf->bodyFooter = Table::buildConfig($bodyFooterTable);
            //copy styles for body footer table to common section for styles
            $tbConf->styles->bodyFooter = $tbConf->bodyFooter->styles->body;
        }

        return $tbConf;

    }

    public function selectStatement(int $offset = null, int $limit = null, $distinct = false)
    {
        $table = $this->driver->quoteName($this->config->className()::getTableName());
        $columns = $this->config->columns()->toArray();
        $calculatedColumns = array_keys($this->config->calculated->toArray());
        $columns = array_diff($columns, $this->config->extraColumns->toArray());
        $this->mergedFilter = $this->config->tablePreFilter()->mergeWith($this->filter, 'ignore');
        $pivotAliases = $this->config->pivots();
        //if defined pivotItemsSelectBy column, than use it for inner where clause for pivot items
        //else use $groupColumns
        $groupColumns = array_diff($columns, array_keys($pivotAliases->toArray()), $calculatedColumns);

        $sql = false ===$distinct ? 'SELECT': 'SELECT DISTINCT';
        $sql .= "\n";
        $selectList = [];
        $pivPrefilters = [];
        foreach ($columns as $column) {
            if (! isset($pivotAliases->$column)) {
                if (in_array($column, $calculatedColumns)) {
//                    $colParams = $this->config->calculatedColumn($column);
//                    $selectList[] = $colParams->method . '(' . $this->driver->quoteName($colParams->column) . ') AS ' . $this->driver->quoteName($column) ;
                    $selectList[] = $this->calculatedColumnStatement($column, $this->mergedFilter);
                } else {
                    $selectList[] = $this->driver->quoteName($column);
                }
            } else {
                $pivCol = $pivotAliases->$column->column;
                $pivCol = $this->driver->quoteName($pivCol);
                $pivPreFilter = $this->config->pivotPreFilter($column);
                $pivPrefilters[] = $pivPreFilter;
                $pivPreFilter->mergeWith($this->mergedFilter);

                $pivotItemsSelectBy = $this->config->pivotItemsSelectBy($column)->toArray();
                //$pivotItemsSelectBy = empty($pivotItemsSelectBy) ? $groupColumns : $pivotItemsSelectBy;
                $groupColumns = array_unique(array_merge($groupColumns, $pivotItemsSelectBy));

                $order = $this->config->pivotSortByQuotedString($column);

                $pivotSql = '(SELECT jsonb_object_agg(t2.' . $pivCol . ',' . ' t2.numbers)' . "\n";
                $pivotSql .= 'FROM (' . "\n";
                $pivotSql .= 'SELECT' . "\n";
                $pivotSql .= $pivCol . ',' . "\n";
                $pivotSql .= 'count(' . $pivCol . ') AS numbers' . "\n";
                $pivotSql .= 'FROM ' . $table . '  AS t3' . "\n";
                $innerClause_1 = array_map(function($item) {
                    return $this->driver->quoteName('t3.' . $item) . ' = ' . $this->driver->quoteName('t1.' . $item);
                }, $pivotItemsSelectBy);
                $innerClause_1 = empty($innerClause_1) ? '' : ' AND ' . implode(' AND ', $innerClause_1);
                $innerFilterStatement = $pivPreFilter->filterStatement();
                if (! empty($innerClause_1) || !empty($innerFilterStatement)) {
                    $pivotSql .= 'WHERE ' . $innerFilterStatement . $innerClause_1 . "\n";
                }
                $pivotSql .= 'GROUP BY ' . $pivCol . "\n";
                if (! empty($order)) {
                    $pivotSql .= 'ORDER BY ' . $order . "\n";
                }
                $pivotSql .= ') AS t2' . "\n";
                $pivotSql .= ') AS ' . $this->driver->quoteName($column);

                $selectList[] = $pivotSql;
            }
        }
        $this->pivPrefilters = $pivPrefilters;
        $sql .= implode(",\n", $selectList) . "\n";
        $sql .= 'FROM ' . $table . ' AS t1' . "\n";
        $whereClause = $this->mergedFilter->filterStatement();
        if (! empty($whereClause)) {
            $sql .= 'WHERE ' . $whereClause . "\n";
        }
        if (! empty($groupColumns)) {
            $groupColumns = array_map(function($item) {
                return $this->driver->quoteName($item);
            }, $groupColumns);
            $sql .= 'GROUP BY ' . implode(', ', $groupColumns) . "\n";
        }
        $sortByClause = $this->config->sortByQuotedString();
        if (! empty($sortByClause)) {
            $sql .= 'ORDER BY ' . $sortByClause;
        }
        if (! is_null($offset) && $offset > 0) {
            $sql .= "\n";
            $sql .= 'OFFSET ' . $offset;
        }
        if (! is_null($limit) && $limit > 0) {
            $sql .= "\n";
            $sql .= 'LIMIT ' . $limit;
        }
        return $sql;
    }
    public function distinctStatementByColumn(string $column, int $offset = null, int $limit = null)
    {
        $table = $this->driver->quoteName($this->config->className()::getTableName());
        $columns = $this->config->columns()->toArray();
        $calculatedColumns = array_keys($this->config->calculated->toArray());
        $columns = array_diff($columns, $this->config->extraColumns->toArray(), $calculatedColumns);
        $this->mergedFilter = $this->config->tablePreFilter()->mergeWith($this->filter, 'ignore');

        $sql = 'SELECT DISTINCT ' . $column . "\n";
        $sql .= 'FROM ' . $table . "\n";
        $whereClause = $this->mergedFilter->filterStatement();
        if (! empty($whereClause)) {
            $sql .= 'WHERE ' . $whereClause . "\n";
        }
        if (! is_null($offset) && $offset > 0) {
            $sql .= "\n";
            $sql .= 'OFFSET ' . $offset;
        }
        if (! is_null($limit) && $limit > 0) {
            $sql .= "\n";
            $sql .= 'LIMIT ' . $limit;
        }
        return $sql;
     }

     public function distinctColumnValues(string $column, int $offset = null, int $limit = null)
     {
         $limit = is_null($limit) ? 50 : $limit;

         $this->mergedFilter = $this->config->tablePreFilter()->mergeWith($this->filter, 'ignore');

         $sql = $this->distinctStatementByColumn($column, $offset, $limit);
         $params = $this->selectParams();
         $conn = $this->config->connection();
         $res = $conn->query($sql, $params)->fetchAll(\PDO::FETCH_COLUMN, 0);

         return $res;
     }

    public function selectParams()
    {
        $params = [];
        $params = array_merge($params, $this->mergedFilter->filterParams);
        if (empty($this->pivPrefilters)) {
            return $params;
        }
        foreach ($this->pivPrefilters as $preFilter) {
            $params = array_merge($params, $preFilter->filterParams);
        }
        foreach ($this->calculatedColumnFilters as $filter) {
            $params = array_merge($params, $filter->filterParams);
        }
        return $params;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $class
     * @param bool $distinct
     * @return mixed return set of records
     * @throws Exception
     */
    public function getRecords(int $limit = null, int $offset = null, string $class = null, $distinct = false)
    {
        if (! is_null($class) && ! class_exists($class)) {
            throw new Exception('getRecords: class name isn\'t valid');
        }
        $pivotAliases = array_keys($this->config->pivots()->toArray());

        $sql = $this->selectStatement($offset, $limit, $distinct);
        $params = $this->selectParams();
        $queryRes = $this->config->connection()->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($queryRes as $key => $val) {
            foreach ($pivotAliases as $pivCol) {
                if (array_key_exists($pivCol, $val)) {
                    $queryRes[$key][$pivCol] = json_decode($val[$pivCol], true, 512);
                }
            }
            if (! is_null($class)) {
                $queryRes[$key] = new $class($queryRes[$key]);
            }
        }
        return$queryRes;
    }

    public function countStatement()
    {
        //TODO refactor
        $table = $this->driver->quoteName($this->config->className()::getTableName());
        $columns = $this->config->columns()->toArray();
        $columns = array_diff($columns, $this->config->extraColumns->toArray());
        $this->mergedFilter = $this->config->tablePreFilter()->mergeWith($this->filter, 'ignore');
        $pivotAliases = $this->config->pivots();
        $calculated = $this->config->calculatedColumns();
        $groupColumns = array_diff($columns, array_keys($pivotAliases->toArray()), array_keys($calculated->toArray()));

        $sql = 'SELECT' . "\n";
        $selectList = [];
        $this->pivPrefilters = [];
        foreach ($columns as $column) {
            if (! isset($pivotAliases->$column) && ! isset($calculated->$column)) {
                $selectList[] = $this->driver->quoteName($column);
            }
        }
        $sql .= implode(",\n", $selectList) . "\n";
        $sql .= 'FROM ' . $table . "\n";
        $whereClause = $this->mergedFilter->filterStatement();
        if (! empty($whereClause)) {
            $sql .= 'WHERE ' . $whereClause . "\n";
        }
        if (! empty($groupColumns)) {
            $groupColumns = array_map(function($item) {
                return $this->driver->quoteName($item);
            }, $groupColumns);
            $sql .= 'GROUP BY ' . implode(', ', $groupColumns) . "\n";
        }
        $sql = 'SELECT count(*) FROM (' . "\n" . $sql . ') as t1';
        return $sql;
    }
    public function calculatedColumnStatement($alias, $tableFilter = null, $mainTableNameAlias = 't1')
    {
        if (!$this->config->isCalculated($alias)) {
            throw new Exception($alias . ' isn\'t defines as calculated column!');
        }
        $props = $this->config->calculatedColumn($alias);
        $preFilter = $this->config->calculatedColumnPreFilter($alias);
        /**
         * if preFilter for calculated column isn't set - return simple clause
         */
        if (empty($preFilter->toArray())) {
            $sql = $props->method . '(' . $this->driver->quoteName($props->column) . ') AS ' . $this->driver->quoteName($alias);
            return $sql;
        }
        $selectByColumns = $this->config->calculatedColumnSelectBy($alias)->toArray();
        /**
         * if preFilter is set - create select statement
         */
        $tableFilter = !is_null($tableFilter) ? $tableFilter : new SqlFilter($this->config->className());
        $columnFilter = $preFilter->mergeWith($tableFilter);

        $this->calculatedColumnFilters->append($columnFilter);

        //$columns = $this->config->columns()->toArray();
        //$calculatedColumns = array_keys($this->config->calculated->toArray());
        //$pivotAliases = array_keys($this->config->pivots()->toArray());
        //$extraColumns = $this->config->extraColumns()->toArray();
        //$conditionalColumns = array_diff($columns, $pivotAliases, $calculatedColumns, $extraColumns);

        $clauses = [];
        $columnFilterStatement = $columnFilter->filterStatement();
        if (!empty($columnFilterStatement)) {
            $clauses[] = $columnFilterStatement;
        }
        $mainTableNameOriginal = $this->config->className()::getTableName();

        $linkWithMainTable = array_map(function ($item) use ($mainTableNameAlias) {
            return $this->driver->quoteName($item) . ' = ' . $mainTableNameAlias . '.' . $this->driver->quoteName($item);
        }, $selectByColumns);

        $linkWithMainTable = implode(' AND ', $linkWithMainTable);

        if (!empty($linkWithMainTable)) {
            $clauses[] = $linkWithMainTable;
        }
        $resClause = implode(' AND ', $clauses);

        $sql = '(SELECT ' . $props->method . '(' . $this->driver->quoteName($props->column) . ')';
        $sql .= "\n";
        $sql .= 'FROM ' . $mainTableNameOriginal . ' AS ' . 't_' . strtolower($alias);
        $sql .= "\n";
        $sql .= 'WHERE ' . $resClause;
        $sql .= "\n";
        $sql .= ') AS ' . $this->driver->quoteName($alias);

        return $sql;
    }
}
