<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class Vrf
 * @package App\Models
 *
 * @property string $name
 * @property string $rd
 * @property string $comment
 *
 * @property Collection|Network[] $networks
 */
class Vrf extends Model
{
    protected static $schema = [
        'table' => 'network.vrfs',
        'columns' => [
            'name' => ['type' => 'string'],
            'rd' => ['type' => 'string'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'networks' => ['type' => self::HAS_MANY, 'model' => Network::class, 'by' => '__vrf_id']
        ]
    ];
}