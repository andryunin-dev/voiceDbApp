<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class VPortType
 * @package App\Models
 *
 * @property string $type
 * @property Collection|VoicePort[] $ports
 */
class VPortType extends Model
{
    protected static $schema = [
        'table' => 'equipment.voicePortTypes',
        'columns' => [
            'type' => ['type' => 'string']
        ],
        'relations' => [
            'ports' => ['type' => self::HAS_MANY, 'model' => VoicePort::class]
        ]
    ];
}