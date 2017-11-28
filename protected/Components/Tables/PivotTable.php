<?php

namespace App\Components\Tables;

class PivotTable extends Table
{
    public function __construct(PivotTableConfig $tableConfig)
    {
        parent::__construct($tableConfig);
    }
}