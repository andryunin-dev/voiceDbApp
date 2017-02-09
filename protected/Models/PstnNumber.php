<?php

namespace App\Models;

use T4\Orm\Model;

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
