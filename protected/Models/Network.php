<?php

namespace App\Models;

use T4\Orm\Model;

class Network extends Model
{
    protected static $schema = [
        'table' => 'network.networks',
        'columns' => [
            'address' => ['type' => 'string'],
        ],
        'relations' => [
            'host' => ['type' => self::HAS_MANY, 'model' => DataPort::class, 'by' => '__network_id']
        ]
    ];
}