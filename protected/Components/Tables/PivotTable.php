<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
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
            if (! $this->config->isPivot($col)) {
                $columnsConf->$col = $colConf;
                continue;
            }
            if (! $pivots->$col->display) {
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
            ]
        );
        $tbConf->styles->header->table->classes = $this->config->headerCssClasses->table;
        $tbConf->styles->body->table->classes = $this->config->bodyCssClasses->table;

        return $tbConf;

    }

    public function selectStatement($offset = null, $limit = null)
    {
        $table = $this->driver->quoteName($this->config->className()::getTableName());
        $columns = $this->config->columns()->toArray();
        $columns = array_diff($columns, $this->config->extraColumns->toArray());
        $this->mergedFilter = $this->config->tablePreFilter()->mergeWith($this->filter, 'ignore');
        $this->pivPrefilters = [];
        $pivotAliases = $this->config->pivots();
        $groupColumns = array_diff($columns, array_keys($pivotAliases->toArray()));

        $sql = 'SELECT' . "\n";
        $selectList = [];
        foreach ($columns as $column) {
            if (! isset($pivotAliases->$column)) {
                $selectList[] = $column;
            } else {
                $pivCol = $pivotAliases->$column->column;
                $pivCol = $this->driver->quoteName($pivCol);
                $pivPreFilter = $this->config->pivotPreFilter($column);
                $this->pivPrefilters[] = $pivPreFilter;

                $order = $this->config->pivotSortByQuotedString($column);

                $pivotSql = '(SELECT jsonb_object_agg(t2.' . $pivCol . ',' . ' t2.numbers)' . "\n";
                $pivotSql .= 'FROM (' . "\n";
                $pivotSql .= 'SELECT' . "\n";
                $pivotSql .= $pivCol . ',' . "\n";
                $pivotSql .= 'count(' . $pivCol . ') AS numbers' . "\n";
                $pivotSql .= 'FROM ' . $table . '  AS t3' . "\n";
                $innerClause_1 = array_map(function($item) {
                    return 't3.' . $item . ' = ' . 't1.' . $item;
                }, $groupColumns);
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
                $pivotSql .= ') AS ' . $this->driver->quoteName($column) . "\n";

                $selectList[] = $pivotSql;
            }
        }
        $sql .= implode(",\n\t", $selectList) . "\n";
        $sql .= 'FROM ' . $table . ' AS t1' . "\n";
        $whereClause = $this->config->tablePreFilter()->filterStatement();
        if (! empty($whereClause)) {
            $sql .= 'WHERE ' . $whereClause . "\n";
        }
        if (! empty($groupColumns)) {
            $sql .= 'GROUP BY ' . implode(', ', $groupColumns) . "\n";
        }
        $sql .= 'ORDER BY ' . $this->config->sortByQuotedString();
        return $sql;
    }

}