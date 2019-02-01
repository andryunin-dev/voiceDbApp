<?php

namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class ApiView_Geo
 * @package App\ViewModels
 * @property integer office_id
 * @property integer office_lotus_id
 * @property integer office_status_id
 * @property integer city_id
 * @property integer region_id
 * @property string office
 * @property string office_comment
 * @property string office_status
 * @property string office_address
 * @property string city
 * @property string region
 */
class ApiView_Geo extends Model
{
    protected static $schema = [
        'table' => 'api_view.geo',
        'columns' => [
            'office_id' => ['type' => 'int', 'length' => 'big'],
            'office' => ['type' => 'string'],
            'office_lotus_id' => ['type' => 'int'],
            'office_details' => ['type' => 'json'],
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