<?php

namespace App\Models;


use phpDocumentor\Reflection\Location;
use T4\Orm\Model;

/**
 * Class Appliance
 * @package App\Models
 *
 * @property string $details
 * @property string $comment
 * @property Software $software
 * @property Platform $platform
 * @property Vendor $vendor
 * @property Cluster $cluster
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
}