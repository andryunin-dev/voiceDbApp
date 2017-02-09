<?php

namespace App\Models;


use T4\Orm\Model;

/**
 * Class ApplianceType
 * @package App\Models
 *
 * @property string $details
 */
class ApplianceType extends Model
{
    protected static $schema = [
        'table' => 'equipment.applianceTypes',
        'columns' => [
            'type' => ['type' => 'string'],
        ],
        'relations' => [
            'appliances' => ['type' => self::HAS_MANY, 'model' => Appliance::class, 'by' => '__type_id']
        ]
    ];
}