<?php

namespace App\Models;

use T4\Orm\Model;

/**
 * Class Cluster
 * @package App\Models
 *
 * @property string $title
 * @property string $details
 * @property string $comment
 */
class Cluster extends Model
{
    protected static $schema = [
        'table' => 'equipment.clusters',
        'columns' => [
            'title' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'appliances' => ['type' => self::HAS_MANY, 'model' => Appliance::class]
        ]
    ];
}