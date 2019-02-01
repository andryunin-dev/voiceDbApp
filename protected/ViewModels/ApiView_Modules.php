<?php

namespace App\ViewModels;

use T4\Orm\Model;

class ApiView_Modules extends Model
{
    protected static $schema = [
        'table' => 'api_view.modules',
        'columns' => [
            'dev_id' => ['type' => 'int', 'length' => 'big'],
            'module_item_id' => ['type' => 'int', 'length' => 'big'],
            'module_id' => ['type' => 'int', 'length' => 'big'],
            'module_item_location_id' => ['type' => 'int', 'length' => 'big'],
            'module_item_details' => ['type' => 'json'],
            'module_item_comment' => ['type' => 'string'],
            'module_item_sn' => ['type' => 'string'],
            'module_item_inv_number' => ['type' => 'string'],
            'module_item_in_use' => ['type' => 'boolean'],
            'module_item_not_found' => ['type' => 'boolean'],
            'module_item_last_update' => ['type' => 'datetime'],
            'module' => ['type' => 'string'],
            'module_descr' => ['type' => 'string'],
        ]
    ];
    
    
    protected function beforeSave()
    {
        return false;
    }
}