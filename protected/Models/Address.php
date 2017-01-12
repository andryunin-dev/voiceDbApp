<?php

namespace App\Models;

use T4\Orm\Model;

/**
 * Class Address
 * @package App\Models
 *
 * @property string $address Office's post address
 * @property City $city
 * @property Office $office
 */
class Address extends Model
{
    protected static $schema = [
        'table' => 'geolocation.addresses',
        'columns' => [
            'address' => ['type' => 'text']
        ],
        'relations' => [
            'city' => ['type' => self::BELONGS_TO, 'model' => City::class],
            'office' => ['type' => self::HAS_ONE, 'model' => Office::class]
        ]
    ];
}