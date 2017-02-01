<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class SoftwareItem
 * @package App\Models
 *
 * @property string $version
 * @property string $detail
 * @property string $comment
 * @property Software $software
 * @property Collection|Appliance[] $appliances
 */
class SoftwareItem extends Model
{
    protected static $schema = [
        'table' => 'equipment.softwareItems',
        'columns' => [
            'version' => ['type' => 'string'],
            'details' => ['type' => 'jsonb'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'software' => ['type' => self::BELONGS_TO, 'model' => Software::class],
            'appliances' => ['type' => self::HAS_MANY, 'model' => Appliance::class]
        ]
    ];
}