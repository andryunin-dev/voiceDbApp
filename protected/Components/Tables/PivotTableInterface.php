<?php

namespace App\Components\Tables;

interface PivotTableInterface extends TableInterface
{
    public function findPivotItems(string $pivotAlias);
}