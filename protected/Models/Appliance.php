<?php

namespace App\Models;

use App\Storage1CModels\Appliance1C;
use T4\Core\Collection;
use T4\Core\Exception;
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
    public function getManagementIpPort()
    {
        $dataPort = $this->dataPorts->filter(
            function($dataPort) {
                return true === $dataPort->isManagement;
            }
        )->first();

        if (!is_null($dataPort)) {
            return $dataPort;
        }

        return false;
    }

    /**
     * @param string $vendorTitle
     * @param string $platformSerial
     * @return Appliance|bool
     */
    public static function findByVendorTitlePlatformSerial(string $vendorTitle, string $platformSerial)
    {
        $platformItems = PlatformItem::findAllByColumn('serialNumber', $platformSerial);
        $platformItem = $platformItems->filter(
            function ($platformItem) use ($vendorTitle) {
                return $vendorTitle == $platformItem->platform->vendor->title;
            }
        )->first();

        if (is_null($platformItem)) {
            return false;
        } else {
            return $platformItem->appliance;
        }
    }

    /**
     * @param string $type
     * @param string $platformSerial
     * @return Appliance|bool
     */
    public static function findByTypeSerial(string $type, string $platformSerial)
    {
        return (self::findAll())->filter(
            function($appliance) use ($type, $platformSerial) {
                return $type == $appliance->type->type && $platformSerial == $appliance->platform->serialNumber;
            }
        )->first();
    }

    /**
     * @param string $type
     * @return Collection Appliance|bool
     */
    public static function findAllByType(string $type)
    {
        return ApplianceType::findByColumn('type', $type)->appliances;
    }

    /**
     * @param string $ip
     * @return Appliance|bool
     */
    public static function findByManagementIP(string $ip)
    {
        $dataPort = DataPort::findByColumn('ipAddress', $ip);
        return (false !== $dataPort && true === $dataPort->isManagement) ? $dataPort->appliance : false;
    }

    public function delete()
    {
        if ($this->isNew()) {
            return false;
        }

        foreach ($this->modules as $module) {
            $module->delete();
        }
        foreach ($this->dataPorts as $dataPort) {
            $dataPort->delete();
        }
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
}
