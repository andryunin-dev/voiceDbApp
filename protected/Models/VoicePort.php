<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class VoicePort
 * @package App\Models
 *
 * @property string $details
 * @property string $comment
 * @property Appliance $appliance
 * @property VPortType $portType
 * @property Collection|PstnNumber[] $pstnNumbers
 */
class VoicePort extends Model
{
    protected static $schema = [
        'table' => 'equipment.voicePorts',
        'columns' => [
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'text']
        ],
        'relations' => [
            'appliance' => ['type' => self::BELONGS_TO, 'model' => Appliance::class],
            'portType' => ['type' => self::BELONGS_TO, 'model' => VPortType::class, 'on' => '__type_port_id'],
            'pstnNumbers' => ['type' => self::HAS_MANY, 'model' => PstnNumber::class, 'by' => '__voice_port_id']
        ]
    ];
}