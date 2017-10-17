<?php

namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class Geo_View
 * @package App\ViewModels
 *
 * @property string $office_id
 * @property string $office
 * @property int $lotusId
 * @property int $people
 * @property int $officeStatus_id
 * @property string $officeStatus
 * @property string $address
 * @property int $city_id
 * @property string $city
 * @property string $region
 * @property int $region_id
 * @property string $regCenter
 */
class Geo_View extends Model
{
    protected static $schema = [
        'table' => 'view.geo',
        'columns' => [
            'office_id' => ['type' => 'int', 'length' => 'big'],
            'officeStatus_id' => ['type' => 'int', 'length' => 'big'],
            'city_id' => ['type' => 'int', 'length' => 'big'],
            'region_id' => ['type' => 'int', 'length' => 'big'],
        ]
    ];
    protected function beforeSave()
    {
        return false;
    }
}