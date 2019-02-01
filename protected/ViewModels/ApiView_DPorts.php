<?php

namespace App\ViewModels;

use T4\Orm\Model;

class ApiView_DPorts extends Model
{
    protected static $schema = [
        'table' => 'api_view.dports',
        'columns' => [
            'dev_id' => ['type' => 'int', 'length' => 'big'],
            'port_id' => ['type' => 'int', 'length' => 'big'],
            'port_net_id' => ['type' => 'int', 'length' => 'big'],
            'port_type_id' => ['type' => 'int', 'length' => 'big'],
            'port_vrf_id' => ['type' => 'int', 'length' => 'big'],
            'port_ip' => ['type' => 'string'],
            'port_last_update' => ['type' => 'datetime'],
            'port_comment' => ['type' => 'string'],
            'port_details' => ['type' => 'json'],
            'port_is_mng' => ['type' => 'boolean'],
            'port_mac' => ['type' => 'string'],
            'port_mask_len' => ['type' => 'integer'],
            'port_type' => ['type' => 'string'],
        ]
    ];
    
    
    protected function beforeSave()
    {
        return false;
    }
}