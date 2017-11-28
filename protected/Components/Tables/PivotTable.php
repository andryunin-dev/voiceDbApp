<?php

namespace App\Components\Tables;

/**
 * Class PivotTable
 * @package App\Components\Tables
 *
 * @property PivotTableConfig $config
 */
class PivotTable extends Table
{
    public function __construct(PivotTableConfig $tableConfig)
    {
        parent::__construct($tableConfig);
    }

    public function findPivotItems(string $pivotAlias)
    {
        $pivotPreFilter = $this->config->pivotPreFilter($pivotAlias);
    }
}