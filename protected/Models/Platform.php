<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class Platform
 * @package App\Models
 *
 * @property string $title
 * @property jsonb $detail
 * @property string $comment
 * @property Collection|Appliance[] $appliances
 */
class Platform extends Model
{
    protected static $schema = [
        'table' => 'equipment.platforms',
        'columns' => [
            'title' => ['type' => 'string'],
            'details' => ['type' => 'jsonb'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'appliances' => ['type' => self::HAS_MANY, 'model' => Appliance::class]
        ]
    ];
}