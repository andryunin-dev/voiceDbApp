<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class ModuleItem
 * @package App\Models
 *
 * @property string $serialNumber
 * @property string $inventoryNumber
 * @property string $details
 * @property string $comment
 * @property Module $module
 * @property Appliance $appliance
 */
class ModuleItem extends Model
{
    protected static $schema = [
        'table' => 'equipment.moduleItems',
        'columns' => [
            'serialNumber' => ['type' => 'string'],
            'inventoryNumber' => ['type' => 'string'],
            'details' => ['type' => 'jsonb'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'module' => ['type' => self::BELONGS_TO, 'model' => Module::class],
            'appliance' => ['type' => self::HAS_ONE, 'model' => Appliance::class]
        ]
    ];
}