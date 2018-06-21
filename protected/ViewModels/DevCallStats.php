<?php

namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class DevCallStats
 * @package App\ViewModels
 *
 * @property string appliance
 * @property \DateTime date
 * @property int call_quantities_current_day
 * @property int call_quantities_current_month
 * @property int call_quantities_last_month
 * @property int call_quantities_before_last_month
 */
class DevCallStats extends Model
{
    protected static $schema = [
        'db' => 'cdr',
        'table' => 'cdr_call_activ.dev_call_stats',
        'columns' => [
            'appliance' => ['type' => 'string'],
            'date' => ['type' => 'date'],
            'call_quantities_current_day' => ['type' => 'int'],
            'call_quantities_current_month' => ['type' => 'int'],
            'call_quantities_last_month' => ['type' => 'int'],
            'call_quantities_before_last_month' => ['type' => 'int'],
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }
}
