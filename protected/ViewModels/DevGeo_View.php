<?php

namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class GeoDev_View
 * @package App\ViewModels
 *
 * @property string $region
 * @property int $region_id
 * @property string $city
 * @property int $city_id
 * @property string $office
 * @property int $office_id
 * @property int $lotusId
 * @property int $appliance_id
 * @property int $location_id
 * @property string $appLastUpdate
 * @property int $appAge
 * @property bool $appInUse
 * @property int $hostname
 * @property string $appDetails
 * @property string $appComment
 * @property string $appType_id
 * @property string $appType
 * @property bool $isHW
 * @property string $appSortOrder
 * @property int $cluster_id
 * @property string $clusterTitle
 * @property string $clusterDetails
 * @property string $clusterComment
 * @property int $platformVendor_id
 * @property int $platformVendor
 * @property int $platformItem_id
 * @property int $platformTitle
 * @property int $platform_id
 * @property string $platformSerial
 * @property int $softwareVendor_id
 * @property int $softwareVendor
 * @property int $softwareItem_id
 * @property int $softwareTitle
 * @property int $softwareVersion
 * @property string $managementIp
 * @inheritdoc
 */
class DevGeo_View extends Model
{
    use PivotReportTrait;

    protected static $schema = [
        'table' => 'view.dev_geo',
        'columns' => [
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
            'appliance_id' => ['type' => 'int', 'length' => 'big'],
            'location_id' => ['type' => 'int', 'length' => 'big'],
            'appLastUpdate' => ['type' => 'datetime'],
            'appAge' => ['type' => 'int'],
            'appInUse' => ['type' => 'boolean'],
            'hostname' => ['type' => 'string'],
            'appDetails' => ['type' => 'jsonb'],
            'appComment' => ['type' => 'string'],
            'appType_id' => ['type' => 'int', 'length' => 'big'],
            'appType' => ['type' => 'string'],
            'cluster_id' => ['type' => 'int', 'length' => 'big'],
            'clusterTitle' => ['type' => 'string'],
            'clusterDetails' => ['type' => 'jsonb'],
            'clusterComment' => ['type' => 'string'],
            'platformVendor_id' => ['type' => 'int', 'length' => 'big'],
            'platformVendor' => ['type' => 'string'],
            'platformItem_id' => ['type' => 'int', 'length' => 'big'],
            'platformTitle' => ['type' => 'string'],
            'isHW' => ['type' => 'boolean'],
            'platform_id' => ['type' => 'int', 'length' => 'big'],
            'softwareVendor_id' => ['type' => 'int', 'length' => 'big'],
            'softwareVendor' => ['type' => 'string'],
            'softwareItem_id' => ['type' => 'int', 'length' => 'big'],
            'software_id' => ['type' => 'int', 'length' => 'big'],
            'softwareTitle' => ['type' => 'string'],
            'softwareVersion' => ['type' => 'string']
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }
}