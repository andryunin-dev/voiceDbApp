<?php


namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class GeoDevStat
 * @package App\ViewModels
 *
 */
class DevGeoPeople_1 extends Model
{
    protected static $schema = [
        'table' => 'view.dev_geo_people_1',
        'columns' => [
            'regCenter' => ['type' => 'string'],
            'region' => ['type' => 'string'],
            'region_id' => ['type' => 'int', 'length' => 'big'],
            'city' => ['type' => 'string'],
            'city_id' => ['type' => 'int', 'length' => 'big'],
            'office' => ['type' => 'string'],
            'office_id' => ['type' => 'int', 'length' => 'big'],
            'lotusId' => ['type' => 'int'],
            'people' => ['type' => 'string'],
            'appliance_id' => ['type' => 'int', 'length' => 'big'],
            'appLastUpdate' => ['type' => 'datetime'],
            'appAge' => ['type' => 'int'],
            'appInUse' => ['type' => 'boolean'],
            'appType_id' => ['type' => 'int', 'length' => 'big'],
            'appType' => ['type' => 'string'],
            'platformVendor_id' => ['type' => 'int', 'length' => 'big'],
            'platformVendor' => ['type' => 'string'],
            'platformItem_id' => ['type' => 'int', 'length' => 'big'],
            'platformTitle' => ['type' => 'string'],
            'platform_id' => ['type' => 'int', 'length' => 'big'],
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }
}