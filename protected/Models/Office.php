<?php

namespace App\Models;

use T4\Orm\Model;

/**
 * Class Office
 * @package App\Models
 *
 * @property string $title Office title
 * @property string $details Any additional info about office in JSONB format
 * @property string $comment
 * @property Region $region
 * @property City $city
 * @property Address $address
 */
class Office extends Model
{
    protected static $schema = [
        'table' => 'company.offices',
        'columns' => [
            'title' => ['type' => 'string'],
            'details' => ['type' => 'jsonb'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'region' => ['type' => self::BELONGS_TO, 'model' => Region::class],
            'city' => ['type' => self::BELONGS_TO, 'model' => City::class],
            'address' => ['type' => self::BELONGS_TO, 'model' => Address::class]
        ]
    ];

}