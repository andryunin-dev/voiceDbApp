<?php

namespace App\ViewModels;

use T4\Orm\Model;

class HdsAgentsPhonesStatView extends Model
{
    use PivotReportTrait;

    protected static $schema = [
        'table' => 'view.hds_stats_by_yesterday_phones',
        'columns' => [
            'applianceId' => ['type' => 'int', 'length' => 'big'],
            'prefix' => ['type' => 'string'],
            'platformId' => ['type' => 'int'],
            'platformTitle' => ['type' => 'string'],
            'officeId' => ['type' => 'int'],
            'lotusId' => ['type' => 'int'],
            'officeTitle' => ['type' => 'string'],
            'regionId' => ['type' => 'int'],
            'regionTitle' => ['type' => 'string'],
            'cityId' => ['type' => 'int'],
            'cityTitle' => ['type' => 'string'],
            'employees' => ['type' => 'int'],
            'hwPhonesActive' => ['type' => 'int'],
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }
}
