<?php

namespace App\Models;

use T4\Orm\Model;

/**
 * Class City
 * @package App\Models
 *
 * @property string $title City's title
 */
class City extends Model
{
    protected static $schema = [
        'table' => 'geolocation.cities',
        'columns' => [
            'title' => ['type' => 'string']
        ]
    ];
}