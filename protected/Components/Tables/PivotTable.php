<?php

namespace App\Components\Tables;

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
        $res = new Std();
        foreach ($this->config->columns as $col => $colConf) {
            if (! $this->config->isPivot($col)) {
                $res->$col = $colConf;
                continue;
            }
            $pivotItems = $this->findPivotItems($col);
            $propsTemplate = $this->config->columnPropertiesTemplate->merge(new Std($this->pivotItemProperties));
            foreach ($pivotItems as $idx => $item) {
                $res->$item = $propsTemplate;
                $res->$item->pivotColumnAlias = $col;
                $res->$item->id = $col . '_' . $idx;
                $res->$item->name = $item;
                $res->$item->width = $this->config->pivotWidthItems($col);
            }
        }
        return $res;
    }

    public function selectStatement($offset = null, $limit = null)
    {

    }

}