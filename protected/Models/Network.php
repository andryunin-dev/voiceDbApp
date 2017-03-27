<?php

namespace App\Models;

use App\Components\Ip;
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

    protected static $extensions = ['tree'];

    protected $ip;

    protected function validateAddress($val)
    {
        $this->ip = new Ip($val);
        return true;
    }

    protected function validate()
    {

    }
}