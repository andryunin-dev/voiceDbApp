<?php

namespace App\Models;

use T4\Orm\Model;

/**
 * Class DataPort
 * @package App\Models
 *
 * @property string $ipAddress
 * @property json $details
 * @property string $comment
 * @property Appliance $appliance
 * @property DPortType $portType
 */
class DataPort extends Model
{
    protected static $schema = [
        'table' => 'equipment.dataPorts',
        'columns' => [
            'ipAddress' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'text']
        ],
        'relations' => [
            'appliance' => ['type' => self::BELONGS_TO, 'model' => Appliance::class],
            'portType' => ['type' => self::BELONGS_TO, 'model' => DPortType::class]
        ]
    ];
}
