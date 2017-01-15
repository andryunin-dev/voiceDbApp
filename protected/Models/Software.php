<?php

namespace App\Models;

use T4\Orm\Model;

/**
 * Class Software
 * @package App\Models
 *
 * @property string $title
 * @property string $version
 * @property jsonb $detail
 * @property string $comment
 * @property Collection|Appliance[] $appliances
 */
class Software extends Model
{
    protected static $schema = [
        'table' => 'equipment.software',
        'columns' => [
            'title' => ['type' => 'string'],
            'version' => ['type' => 'string'],
            'details' => ['type' => 'jsonb'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'appliances' => ['type' => self::HAS_MANY, 'model' => Appliance::class]
        ]
    ];
}