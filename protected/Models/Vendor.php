<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class Vendor
 * @package App\Models
 *
 * @property string $name
 * @property Collection $office
 */
class Vendor extends Model
{
    protected static $schema = [
        'table' => 'equipment.vendors',
        'columns' => [
            'name' => ['type' => 'string']
        ],
        'relations' => [
            'appliances' => ['type' => self::HAS_MANY]
        ]
    ];
}