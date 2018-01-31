<?php
namespace App\ViewModels;

use T4\Orm\Model;


/**
 * Class DevPhoneInfoGeo
 * @package App\ViewModels
 */
class DevPhoneInfoGeo extends Model
{

    protected static $schema = [
        'table' => 'view.dev_phone_info_geo',
        'columns' => [
            'lotus_regCenter' => ['type' => 'string'],
            'region' => ['type' => 'string'],
            'lotus_region' => ['type' => 'string'],
            'region_id' => ['type' => 'int', 'length' => 'big'],
            'city' => ['type' => 'string'],
            'lotus_city' => ['type' => 'string'],
            'city_id' => ['type' => 'int', 'length' => 'big'],
            'office' => ['type' => 'string'],
            'lotus_office' => ['type' => 'string'],
            'office_id' => ['type' => 'int', 'length' => 'big'],
            'lotusId' => ['type' => 'int'],
            'lotus_lotusId' => ['type' => 'int'],
            'officeComment' => ['type' => 'string'],
            'officeDetails' => ['type' => 'jsonb'],
            'officeAddress' => ['type' => 'string'],
            'lotus_officeAddress' => ['type' => 'string'],
            'lotus_employees' => ['type' => 'int'],
            'lotus_lastRefresh' => ['type' => 'datetime'],
            'appliance_id' => ['type' => 'int', 'length' => 'big'],
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
            'platformSerial' => ['type' => 'string'],
            'softwareVendor_id' => ['type' => 'int', 'length' => 'big'],
            'softwareVendor' => ['type' => 'string'],
            'softwareItem_id' => ['type' => 'int', 'length' => 'big'],
            'software_id' => ['type' => 'int', 'length' => 'big'],
            'softwareTitle' => ['type' => 'string'],
            'softwareVersion' => ['type' => 'string'],
            'name' => ['type' => 'string'],
            'model' => ['type' => 'string'],
            'prefix' => ['type' => 'int'],
            'phoneDN' => ['type' => 'int'],
            'status' => ['type' => 'string'],
            'phoneDescription' => ['type' => 'string'],
            'css' => ['type' => 'string'],
            'devicePool' => ['type' => 'string'],
            'alertingName' => ['type' => 'string'],
            'partition' => ['type' => 'string'],
            'timezone' => ['type' => 'string'], //
            'dhcpEnabled' => ['type' => 'boolean'],
            'dhcpServer' => ['type' => 'string'],
            'domainName' => ['type' => 'string'],
            'tftpServer1' => ['type' => 'string'],
            'tftpServer2' => ['type' => 'string'],
            'defaultRouter' => ['type' => 'string'],
            'dnsServer1' => ['type' => 'string'],
            'dnsServer2' => ['type' => 'string'],
            'callManager1' => ['type' => 'string'],
            'callManager2' => ['type' => 'string'],
            'callManager3' => ['type' => 'string'],
            'callManager4' => ['type' => 'string'],
            'vlanId' => ['type' => 'int'],
            'userLocale' => ['type' => 'string'],
            'cdpNeighborDeviceId' => ['type' => 'string'],
            'cdpNeighborIP' => ['type' => 'string'],
            'cdpNeighborPort' => ['type' => 'string'],
            'publisherIp' => ['type' => 'string'],
            'unknownLocation' => ['type' => 'boolean'],
            'managementIp' => ['type' => 'string'],
        ]
    ];


    protected function beforeSave()
    {
        return false;
    }
}
