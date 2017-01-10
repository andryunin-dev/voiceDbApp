<?php

namespace App\Models;

use T4\Orm\Model;

/**
 * Class Address
 * @package App\Models
 *
 * @property string $address Office's post address
 */
class Address extends Model
{
    protected static $schema = [
        'table' => 'geolocation.addresses',
        'columns' => [
            'address' => ['type' => 'text']
        ]
    ];
}