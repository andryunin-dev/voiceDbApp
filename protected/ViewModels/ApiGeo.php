<?php

namespace App\ViewModels;

class ApiGeo
{
    protected static $schema = [
        'table' => 'view.api_view',
        'columns' => [
            'location_id' => ['type' => 'int', 'length' => 'big'],
            'office' => ['type' => 'string'],
            'office_lotus_id' => ['type' => 'int'],
            'office_details' => ['type' => 'string'],
            'office_comment' => ['type' => 'string'],
            'office_status_id' => ['type' => 'int', 'length' => 'big'],
            'office_status' => ['type' => 'string'],
            'office_address' => ['type' => 'string'],
            'city_id' => ['type' => 'int', 'length' => 'big'],
            'city' => ['type' => 'string'],
            'region_id' => ['type' => 'int', 'length' => 'big'],
            'region' => ['type' => 'string'],
        ]
    ];
    
    protected function beforeSave()
    {
        return false;
    }
}