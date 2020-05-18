<?php

namespace App\Models;

use App\Components\IpTools;
use App\Storage1CModels\Appliance1C;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class Appliance
 * @package App\Models
 *
 * @property string $details
 * @property string $comment
 * @property string $lastUpdate
 * @property boolean $inUse
 * @property boolean $untrustedLocation
 *
 * @property ApplianceType $type
 * @property Office $location
 * @property Cluster $cluster
 * @property Vendor $vendor
 * @property PlatformItem $platform
 * @property SoftwareItem $software
 *
 * @property Collection|VoicePort[] $voicePorts
 * @property Collection|DataPort[] $dataPorts
 * @property Collection|ModuleItem[] $modules
 * @property PhoneInfo $phoneInfo
 * @property Appliance1C $appliance1C
 */
class Appliance extends Model
{
    const LIFETIME = 73; // hours
    const SQL = [
        'findBy_Vendor_SerialNumber' => '
            SELECT appliance.*
            FROM equipment.appliances appliance
              JOIN equipment."platformItems" platformItem ON platformItem.__id = appliance.__platform_item_id
              JOIN equipment.platforms platform ON platform.__id = platformItem.__platform_id
              JOIN equipment.vendors vendor ON vendor.__id = platform.__vendor_id
            WHERE platformItem."serialNumber" = :serialnumber AND vendor.title = :vendor_title',
        'findBy_ManagementIp_Vrf' => '
            SELECT appliance.*
            FROM equipment.appliances appliance
              JOIN equipment."dataPorts" dataport ON dataport.__appliance_id = appliance.__id
              JOIN network.networks network ON network.__id = dataport.__network_id
              JOIN network.vrfs vrf ON vrf.__id = network.__vrf_id
            WHERE dataport."ipAddress" = :ip AND dataport."isManagement" IS TRUE AND vrf.name = :vrf_name',
        'findManagementDPort' => '
            SELECT dataport.*
            FROM equipment."dataPorts" dataport
              JOIN equipment.appliances appliance ON appliance.__id = dataport.__appliance_id
            WHERE appliance.__id = :appliance_pk AND dataport."isManagement" IS TRUE',
        'find_dataport_by_ip_vrf' => '
            SELECT dataport.*
            FROM equipment."dataPorts" dataport
              JOIN network.networks network ON dataport.__network_id = network.__id
              JOIN network.vrfs vrf ON network.__vrf_id = vrf.__id
            WHERE dataport.__appliance_id = :appliance_id AND dataport."ipAddress" = :ip AND vrf.__id = :vrf_id',
        'find_by_net_type' => '
            SELECT *
            FROM equipment.appliances appliance
               JOIN equipment."applianceTypes" appliance_type ON appliance.__type_id = appliance_type.__id
               JOIN equipment."dataPorts" dataport ON appliance.__id = dataport.__appliance_id
            WHERE appliance_type.type = :app_type AND ((date_part(\'epoch\' :: TEXT, age(now(), dataport."lastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER < 73 AND dataport.__network_id = :net_id',
    ];

    protected static $schema = [
        'table' => 'equipment.appliances',
        'columns' => [
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'text'],
            'lastUpdate' => ['type' => 'datetime'],
            'inUse' => ['type' => 'boolean'],
            'untrustedLocation' => ['type' => 'boolean'],
        ],
        'relations' => [
            'type' => ['type' => self::BELONGS_TO, 'model' => ApplianceType::class, 'by' => '__type_id'],
            'location' => ['type' => self::BELONGS_TO, 'model' => Office::class, 'by' => '__location_id'],
            'cluster' => ['type' => self::BELONGS_TO, 'model' => Cluster::class],
            'vendor' => ['type' => self::BELONGS_TO, 'model' => Vendor::class],
            'platform' => ['type' => self::BELONGS_TO, 'model' => PlatformItem::class, 'by' => '__platform_item_id'],
            'software' => ['type' => self::BELONGS_TO, 'model' => SoftwareItem::class, 'by' => '__software_item_id'],
            'voicePorts' => ['type' => self::HAS_MANY, 'model' => VoicePort::class],
            'dataPorts' => ['type' => self::HAS_MANY, 'model' => DataPort::class],
            'modules' => ['type' => self::HAS_MANY, 'model' => ModuleItem::class],
            'phoneInfo' => ['type' => self::HAS_ONE, 'model' => PhoneInfo::class],
            'appliance1C' => ['type' => self::HAS_ONE, 'model' => Appliance1C::class, 'by' => '__voice_appliance_id'],
        ]
    ];

    protected function validate()
    {
        if (! ($this->location instanceof Office)) {
            throw new Exception('Appliance: Неверный тип Office');
        }
        if (! ($this->vendor instanceof Vendor)) {
            throw new Exception('Appliance: Неверный тип Vendor');
        }
        if (! ($this->platform instanceof PlatformItem)) {
            throw new Exception('Appliance: Неверный тип Platform');
        }
        if (! ($this->software instanceof SoftwareItem)) {
            throw new Exception('Appliance: Неверный тип Software');
        }
        if (! ($this->type instanceof ApplianceType)) {
            throw new Exception('Appliance: Неверный тип ApplianceType');
        }
        if (!(is_null($this->cluster)) && !($this->cluster instanceof Cluster)) {
            throw new Exception('Appliance: Неверный тип Cluster');
        }

        $appliance = $this->platform->appliance;

        if (true === $this->isNew && ($appliance instanceof Appliance)) {
            throw new Exception('Такой Appliance уже существует');
        }
        if (true === $this->isUpdated && ($appliance instanceof Appliance) && ($appliance->getPk() != $this->getPk())) {
            throw new Exception('Такой Appliance уже существует');
        }

        return true;
    }

    protected function beforeSave()
    {
        return parent::beforeSave();
    }

    public function lastUpdateDate()
    {
        return $this->lastUpdate ? ((new \DateTime($this->lastUpdate))->setTimezone(new \DateTimeZone('Europe/Moscow')))->format('d.m.Y') : null;
    }

    public function lastUpdateDateTime()
    {
        return $this->lastUpdate ? ('last update: ' . ((new \DateTime($this->lastUpdate))->setTimezone(new \DateTimeZone('Europe/Moscow')))->format('d.m.Y H:i \M\S\K(P)')) : null;
    }

    /**
     * @return string managementIp|bool
     */
    public function getManagementIp()
    {
        $dataPort = $this->dataPorts->filter(
            function($dataPort) {
                return true === $dataPort->isManagement;
            }
        )->first();

        if (!is_null($dataPort)) {
            return $dataPort->ipAddress;
        }

        return false;
    }
    public function getNoManagementPorts()
    {
        $result = $this->dataPorts->filter(
            function ($dPort) {
                return true !== $dPort->isManagement;
            }
        );
        return $result;
    }
    /**
     * Find Appliance by SerialNumber and VendorTitle
     *
     * @param string $serialNumber
     * @param string $vendorTitle
     * @return mixed
     */
    public static function findBySerialVendor(string $serialNumber, string $vendorTitle)
    {
        $query = new Query(self::SQL['findBy_Vendor_SerialNumber']);
        return self::findByQuery($query, [':serialnumber' => $serialNumber, ':vendor_title' => $vendorTitle]);
    }

    /**
     * @param string $type
     * @return mixed
     */
    public static function findAllByType(string $type)
    {
        return ApplianceType::findByColumn('type', $type)->appliances;
    }

    /**
     * @param string $ip
     * @param string $vrf_name
     * @return mixed
     */
    public static function findByManagementIpVrf(string $ip, string $vrf_name)
    {
        $query = new Query(self::SQL['findBy_ManagementIp_Vrf']);
        return self::findByQuery($query, [':ip' => $ip, ':vrf_name' => $vrf_name]);
    }

    /**
     * @param string $cidrIp
     * @return Appliance|bool - Router сети которой принадлежит $cidrIp
     */
    public static function findRouterByNet(string $cidrIp)
    {
        if (false === $cidrIp = new IpTools($cidrIp)) {
            return false;
        }
        $appliances = false;
        if (false !== $network = Network::findByColumn('address', $cidrIp->network.'/'.$cidrIp->masklen)) {
            $query = new Query(self::SQL['find_by_net_type']);
            $appliances = self::findByQuery($query, [':net_id' => $network->getPk(), ':app_type' => ApplianceType::ROUTER]);
        }
        return $appliances;
    }

    /**
     * @param string $ip
     * @param Vrf $vrf
     * @return mixed
     */
    public function findDataPortByIpVrf(string $ip, Vrf $vrf)
    {
        $query = new Query(self::SQL['find_dataport_by_ip_vrf']);
        return DataPort::findByQuery($query, [':appliance_id' => $this->getPk(), ':ip' => $ip, ':vrf_id' => $vrf->getPk()]);
    }

    public function delete()
    {
        if ($this->isNew()) {
            return false;
        }

        foreach ($this->modules as $module) {
            $module->delete();
        }
        $this->deleteDataPorts();
        if (! empty($phoneInfo = $this->phoneInfo)) {
            $phoneInfo->delete();
        }
        if (!is_null($this->appliance1C)) {
            $this->appliance1C->delete();
        }
        $result = parent::delete();
        $this->software->delete();
        $this->platform->delete();
        return $result;
    }

    public function deleteDataPorts(): void
    {
        foreach ($this->dataPorts as $dataPort) {
            $dataPort->delete();
        }
    }

    public function getManagementDPort()
    {
        $query = new Query(self::SQL['findManagementDPort']);
        return DataPort::findByQuery($query, [':appliance_pk' => $this->getPk()]);
    }

    public function uncheckExistManagementDPort(): void
    {
        $existManagementDPort = $this->getManagementDPort();
        if (false !== $existManagementDPort) {
            $existManagementDPort->fill(['isManagement' => false, 'vrf' => $existManagementDPort->vrf])->save();
            if (count($existManagementDPort->errors) > 0) {
                throw new \Exception($existManagementDPort->errors);
            }
        }
    }
}
