<?php

namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class LotusDbData
 * @package App\ViewModels
 *
 * @property int $lotus_id
 * @property string $title
 * @property string $reg_center
 * @property string $region
 * @property string $city
 * @property string $address
 * @property int $employees
 * @property string $last_refresh
 */
class LotusDbData extends Model
{
    protected static $schema = [
        'table' => 'view.lotus_db_data',
        'columns' => [
            'lotus_id' => ['type' => 'int'],
            'title' => ['type' => 'string'],
            'reg_center' => ['type' => 'string'],
            'region' => ['type' => 'string'],
            'city' => ['type' => 'string'],
            'address' => ['type' => 'string'],
            'employees' => ['type' => 'int'],
            'last_refresh' => ['type' => 'datetime'],
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }

}