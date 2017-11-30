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
                $columnsConf->$item = $propsTemplate;
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

    }

}