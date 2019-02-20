<?php

namespace App\ViewModels;

use T4\Orm\Model;

class ApiView_Networks extends Model
{
    const PK = 'net_id';
    protected static $schema = [
        'table' => 'api_view.networks',
        'columns' => [
            'net_id' => ['type' => 'int', 'length' => 'big'],
            'vlan_id' => ['type' => 'int', 'length' => 'big'],
            'vrf_id' => ['type' => 'int', 'length' => 'big'],
            'net_ip' => ['type' => 'text'],
            'net_comment' => ['type' => 'text'],
            'vlan_number' => ['type' => 'int'],
            'vlan_name' => ['type' => 'text'],
            'vlan_comment' => ['type' => 'text'],
            'vrf_rd' => ['type' => 'text'],
            'vrf_name' => ['type' => 'text'],
            'vrf_comment' => ['type' => 'text'],
        ]
    ];
    protected function beforeSave()
    {
        return false;
    }
}