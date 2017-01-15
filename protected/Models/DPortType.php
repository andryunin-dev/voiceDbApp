<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class DPortType
 * @package App\Models
 *
 * @property string $type
 * @property Collection|DataPort[] $ports
 */
class DPortType extends Model
{
    protected static $schema = [
        'table' => 'equipment.dataPortTypes',
        'columns' => [
            'type' => ['type' => 'string']
        ],
        'relations' => [
            'ports' => ['type' => self::HAS_MANY, 'model' => DataPort::class]

        ]
    ];
}