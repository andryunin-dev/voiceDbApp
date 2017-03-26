<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class Appliance
 * @package App\Models
 *
 * @property string $details
 * @property string $comment
 * @property ApplianceType $type
 * @property Office $location
 * @property Cluster $cluster
 * @property Vendor $vendor
 * @property Platform $platform
 * @property Software $software
 *
 * @property Collection|VoicePort[] $voicePorts
 * @property Collection|DataPort[] $dataPorts
 * @property Collection|ModuleItem[] $modules
 */
class Appliance extends Model
{
    protected static $schema = [
        'table' => 'equipment.appliances',
        'columns' => [
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'text']
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
            'modules' => ['type' => self::HAS_MANY, 'model' => ModuleItem::class]
        ]
    ];

    protected function validate()
    {
        if (false === $this->location) {
            throw new Exception("офис не найден");
        }
        if (false === $this->vendor) {
            throw new Exception('Производитель не найден');
        }
        if (false === $this->platform) {
            throw new Exception('Ошибка при сохранении платформы');
        }
        if (false === $this->software) {
            throw new Exception('Ошибка при сохранении ПО');
        }
        if (false === $this->type) {
            throw new Exception('Тип оборудования не найден');
        }
        return true;
    }

}