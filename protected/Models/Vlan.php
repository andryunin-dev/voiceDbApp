<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class Vlan
 * @package App\Models
 *
 * @property integer $id
 * @property string $name
 * @property string $comment
 *
 * @property Collection|Network[] $networks
 */
class Vlan extends Model
{
    protected static $schema = [
        'table' => 'network.vlans',
        'columns' => [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'networks' => ['type' => self::HAS_MANY, 'model' => Network::class, 'by' => '__vlan_id']
        ]
    ];
}