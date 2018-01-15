<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use SebastianBergmann\CodeCoverage\Report\PHP;
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
        $lowerColumnsConf = new Std();
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
            }
        }
        foreach ($this->config->lowerColumns as $col => $colConf) {
            if (! $this->config->isLowerColumnVisible($col)) {
                continue;
            }
            if (! $this->config->isPivot($col)) {
                $lowerColumnsConf->$col = $colConf;
                continue;
            }
            $pivotWidth += $this->config->lowerColumnConfig($col)->width;
            $pivotItems = $this->findPivotItems($col);
            $propsTemplate = $this->config->columnPropertiesTemplate->merge(new Std($this->pivotItemProperties));
            foreach ($pivotItems as $idx => $item) {
                $lowerColumnsConf->$item = new Std($propsTemplate->toArray());
                $lowerColumnsConf->$item->pivotColumn = $col;
                $lowerColumnsConf->$item->id = $col . '_' . $idx;
                $lowerColumnsConf->$item->name = $item;
                $lowerColumnsConf->$item->width = $this->config->pivotWidthItems($col);
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

        $tbConf->bodyFooter = new Std();
        //todo write methods to set and get config->bodyFooterCssClasses !!!
        //$tbConf->bodyFooter->tableClasses = implode(', ', $this->config->bodyFooterCssClasses->toArray());
        $tbConf->bodyFooter->pivotColumnsWidth = $pivotWidth;
        $tbConf->bodyFooter->columns = $lowerColumnsConf;

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
            ]
        );
        $tbConf->styles->header->table->classes = $this->config->headerCssClasses->table;
        $tbConf->styles->body->table->classes = $this->config->bodyCssClasses->table;

        return $tbConf;

    }

    public function selectStatement(int $offset = null, int $limit = null)
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

        $sql = 'SELECT' . "\n";
        $selectList = [];
        $pivPrefilters = [];
        foreach ($columns as $column) {
            if (! isset($pivotAliases->$column)) {
                if (in_array($column, $calculatedColumns)) {
                    $colParams = $this->config->calculatedColumn($column);
                    $selectList[] = $colParams->method . '(' . $this->driver->quoteName($colParams->column) . ') AS ' . $this->driver->quoteName($column) ;
                } else {
                    $selectList[] = $this->driver->quoteName($column);
                }
            } else {
                $pivCol = $pivotAliases->$column->column;
                $pivCol = $this->driver->quoteName($pivCol);
                $pivPreFilter = $this->config->pivotPreFilter($column);
                $pivPrefilters[] = $pivPreFilter;
                $pivotItemsSelectBy = $this->config->pivotItemsSelectBy($column)->toArray();
                $pivotItemsSelectBy = empty($pivotItemsSelectBy) ? $groupColumns : $pivotItemsSelectBy;
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
        $sql .= 'ORDER BY ' . $this->config->sortByQuotedString();
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
        return $params;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $class
     * @return mixed return set of records
     * @throws Exception
     */
    public function getRecords(int $limit = null, int $offset = null, string $class = null)
    {
        if (! is_null($class) && ! class_exists($class)) {
            throw new Exception('getRecords: class name isn\'t valid');
        }
        $pivotAliases = array_keys($this->config->pivots()->toArray());

        $sql = $this->selectStatement($offset, $limit);
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
}