<?php

namespace App\Models;


use T4\Orm\Model;

/**
 * Class Appliance
 * @package App\Models
 *
 * @property jsonb $details
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
            'software' => ['type' => self::BELONGS_TO, 'model' => Software::class],
            'platform' => ['type' => self::BELONGS_TO, 'model' => Platform::class],
            'vendor' => ['type' => self::BELONGS_TO, 'model' => Vendor::class],
            'cluster' => ['type' => self::BELONGS_TO, 'model' => Cluster::class],
            'voicePorts' => ['type' => self::HAS_MANY, 'model' => VoicePort::class],
            'dataPorts' => ['type' => self::HAS_MANY, 'model' => DataPort::class]
        ]
    ];
}