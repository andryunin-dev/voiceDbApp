<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class Vendor
 * @package App\Models
 *
 * @property string $title
 * @property Collection $appliances
 * @property Collection $software
 * @property Collection $platforms
 */
class Vendor extends Model
{
    protected static $schema = [
        'table' => 'equipment.vendors',
        'columns' => [
            'title' => ['type' => 'string']
        ],
        'relations' => [
            'appliances' => ['type' => self::HAS_MANY, 'model' => Appliance::class],
            'software' => ['type' => self::HAS_MANY, 'model' => Software::class],
            'platforms' => ['type' => self::HAS_MANY, 'model' => Platform::class],
        ]
    ];
}