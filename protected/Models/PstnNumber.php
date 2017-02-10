<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class PstnNumber
 * @package App\Models
 *
 * @property string $number
 * @property string $transferedTo
 * @property string $comment
 *
 * @property VoicePort $voicePort
 * @property Collection|Contract[] $contracts
 */
class PstnNumber extends Model
{
    protected static $schema = [
        'table' => 'telephony.pstnNumbers',
        'columns' => [
            'number' => ['type' => 'string'],
            'transferedTo' => ['type' => 'string'],
            'comment' => ['type' => 'text']
        ],
        'relations' => [
            'voicePort' => ['type' => self::BELONGS_TO, 'model' => VoicePort::class, 'by' => '__voice_port_id'],
            'contracts' => [
                'type' => self::MANY_TO_MANY,
                'model' => Contract::class,
                'pivot' => 'telephony.pstnNumbers_to_contracts',
                'this' => '__pstn_number_id'
            ]
        ]
    ];
}
