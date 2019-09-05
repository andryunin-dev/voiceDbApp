<?php

namespace App\ViewModels;

use T4\Orm\Model;

class ApiView_Employee extends Model
{
    protected static $schema = [
        'table' => 'view.lotus_employees',
        'columns' => [
            'name' => ['type' => 'string'],
            'surname' => ['type' => 'string'],
            'patronymic' => ['type' => 'string'],
            'division' => ['type' => 'string'],
            'work_phone' => ['type' => 'string'],
            'mobile_phone' => ['type' => 'string'],
            'work_email' => ['type' => 'string'],
            'position' => ['type' => 'string'],
            'persons_code' => ['type' => 'int', 'length' => 'big'],
            'net_name' => ['type' => 'string'],
            'domain' => ['type' => 'string'],
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }
}
