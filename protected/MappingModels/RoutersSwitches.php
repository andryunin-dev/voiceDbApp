<?php

namespace App\MappingModels;

use T4\Orm\Model;

class RoutersSwitches extends Model
{
    protected static $schema = [
        'table' => 'mapping.routers_switches',
        'columns' => [
            'oneC_name' => ['type' => 'string'],
            'db_name' => ['type' => 'string'],
        ]
    ];
    
    protected function beforeSave()
    {
        return false;
    }
}