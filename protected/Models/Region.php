<?php

namespace App\Models;

use T4\Orm\Model;

/**
 * Class Region
 * @package App\Models
 *
 * @property string $title Region's title
 */
class Region extends Model
{
    protected static $schema = [
        'table' => 'geolocation.regions',
        'columns' => [
            'title' => ['type' => 'string']
        ]
    ];
}