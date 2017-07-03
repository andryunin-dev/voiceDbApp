<?php

namespace App\ViewModels;

use App\Components\IpTools;
use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class GeoDevModulePort_View
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
 * @property bool $appInUse
 * @property int $hostname
 * @property string $appDetails
 * @property string $appComment
 * @property int $cluster_id
 * @property string $clusterTitle
 * @property string $clusterDetails
 * @property string $clusterComment
 * @property int $platformVendor_id
 * @property int $platformVendor
 * @property int $platformItem_id
 * @property int $platformTitle
 * @property int $softwareVendor_id
 * @property int $softwareVendor
 * @property int $softwareItem_id
 * @property int $softwareTitle
 * @property int $softwareVersion
 * @property string $moduleInfo
 * @property string $portInfo
 *
 * @property string $managementIp
 *
 * @property Collection|ModuleItem_View[] $modules
 * @property Collection|DataPort_View[] $dataPorts
 * @property Collection|DataPort_View[] $noManagementPorts
 */
class GeoDevModulePort_View extends Model
{
    protected static $schema = [
        'table' => 'view.geo_dev_module_port',
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
            'platform_id' => ['type' => 'int', 'length' => 'big'],
            'softwareVendor_id' => ['type' => 'int', 'length' => 'big'],
            'softwareVendor' => ['type' => 'string'],
            'softwareItem_id' => ['type' => 'int', 'length' => 'big'],
            'softwareTitle' => ['type' => 'string'],
            'softwareVersion' => ['type' => 'string'],
            'moduleInfo' => ['type' => 'jsonb'],
            'portInfo' => ['type' => 'jsonb']
        ]
    ];

    protected static $sortOrders = [
        'default' => 'region, city, office, hostname, appliance_id',
        'region' => 'region, city, office, hostname, appliance_id',
        'city' => 'city, office, hostname, appliance_id',
        'office' => 'office, hostname, appliance_id, city',
        'hostname' => 'hostname, appliance_id',
    ];

    public static function sortOrder($orderName = 'default')
    {
        return (array_key_exists($orderName, self::$sortOrders)) ? self::$sortOrders[$orderName] : self::$sortOrders['default'];
    }

    protected function beforeSave()
    {
        return false;
    }

    protected function getManagementIp()
    {
        $portInfo = new Collection(json_decode($this->portInfo));
        $mngIp = $portInfo->filter(
            function($port) {
                return true === $port->isManagement;
            }
        )->first();

        if (! empty($mngIp)) {
            return $mngIp->ipAddress;
        }

        return false;
    }

    protected function getModules()
    {
        $moduleInfo = json_decode($this->moduleInfo);
        if (empty($moduleInfo)) {
            return false;
        }
        $resCollection = new Collection();
        foreach ($moduleInfo as $item) {
            $resCollection->add(new ModuleItem_View($item));
        }
        return $resCollection;
    }

    protected function getDataPorts()
    {
        $portInfo = json_decode($this->portInfo);
        if (empty($portInfo)) {
            return false;
        }
        $resCollection = new Collection();
        foreach ($portInfo as $item) {
            $resCollection->add(new DataPort_View($item));
        }
        return $resCollection;

    }

    protected function getNoManagementPorts()
    {
        $portInfo = json_decode($this->portInfo);
        if (empty($portInfo)) {
            return false;
        }
        $mngIp = $this->managementIp;
        $resCollection = new Collection();
        foreach ($portInfo as $item) {
            $port = new DataPort_View($item);
            if ($port->ipAddress != $mngIp) {
                $resCollection->add($port);
            }
        }
        return $resCollection;
    }

    public function lastUpdateDate()
    {
        return $this->appLastUpdate ? (new \DateTime($this->appLastUpdate))->format('Y-m-d') : null;
    }

    public function lastUpdateDateTime()
    {
        return $this->appLastUpdate ? ('last update: ' . ((new \DateTime($this->appLastUpdate))->setTimezone(new \DateTimeZone('Europe/Moscow')))->format('d.m.Y H:i \M\S\K(P)')) : null;
    }
}