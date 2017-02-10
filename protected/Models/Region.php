<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class Region
 * @package App\Models
 *
 * @property string $title Region's title
 *
 * @property Collection|City[] $cities
 */
class Region extends Model
{
    protected static $schema = [
        'table' => 'geolocation.regions',
        'columns' => [
            'title' => ['type' => 'string']
        ],
        'relations' => [
            'cities' => ['type' => self::HAS_MANY, 'model' => City::class]
        ]
    ];
}