<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class Test
 * @package App\Models
 *
 * @property string $title Region's title
 */
class Test extends Model
{
    protected static $schema = [
        'table' => 'geolocation.regions',
        'columns' => [
            'title' => ['type' => 'string']
        ]
    ];
}