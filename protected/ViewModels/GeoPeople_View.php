<?php

namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class GeoPeople_View
 * @package App\ViewModels
 *
 * @property string $regCenter
 * @property string $region
 * @property int $region_id
 * @property string $city
 * @property int $city_id
 * @property string $office
 * @property int $office_id
 * @property int $lotusId
 * @property int $officeComment
 * @property int $officeDetails
 * @property int $officeAddress
 * @property int $people
  */
class GeoPeople_View extends Model
{
    protected static $schema = [
        'table' => 'view.geo_people',
        'columns' => [
            'regCenter' => ['type' => 'string'],
            'region' => ['type' => 'string'],
            'region_id' => ['type' => 'int', 'length' => 'big'],
            'city' => ['type' => 'string'],
            'city_id' => ['type' => 'int', 'length' => 'big'],
            'office' => ['type' => 'string'],
            'office_id' => ['type' => 'int', 'length' => 'big'],
            'lotusId' => ['type' => 'int'],
            'officeComment' => ['type' => 'string'],
            'officeDetails' => ['type' => 'jsonb'],
            'officeAddress' => ['type' => 'string'],
            'people' => ['type' => 'int'],
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }

}