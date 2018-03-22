<?php

namespace App\ReportModels;

use T4\Orm\Model;

class OfficePlatform extends Model
{
    protected static $schema = [
        'table' => 'view.pivot_test',
        'columns' => [
            'regCenter' => ['type' => 'text'],
            'region' => ['type' => 'text'],
            'office' => ['type' => 'text'],
            'platform' => ['type' => 'text'],
            'quantity' => ['type' => 'integer'],
        ],
        'pivotReport' => [
            'row_name' => ['column' => 'office', 'sqlType' => 'citext'],
            'col_name' => ['column' => 'platform', 'sqlType' => 'citext'],
            'value' => ['column' => 'quantity', 'sqlType' => 'integer'],
            'extra' => ['regCenter', 'region']
        ]
    ];

}