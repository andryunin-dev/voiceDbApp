<?php

namespace App\ViewModels;

use T4\Orm\Model;

class ApiView_Devices extends Model
{
    protected static $schema = [
        'table' => 'api_view.devices',
        'columns' => [
            'dev_id' => ['type' => 'int', 'length' => 'big'],
            'location_id' => ['type' => 'int', 'length' => 'big'],
            'cluster_id' => ['type' => 'int', 'length' => 'big'],
            'platform_item_id' => ['type' => 'int', 'length' => 'big'],
            'software_item_id' => ['type' => 'int', 'length' => 'big'],
            'dev_type_id' => ['type' => 'int', 'length' => 'big'],
            'vendor_id' => ['type' => 'int', 'length' => 'big'],
            'port_id' => ['type' => 'int', 'length' => 'big'],
            'network_id' => ['type' => 'int', 'length' => 'big'],
            'platform_id' => ['type' => 'int', 'length' => 'big'],
            'software_id' => ['type' => 'int', 'length' => 'big'],
            'module_item_id' => ['type' => 'int', 'length' => 'big'],
            'module_id' => ['type' => 'int', 'length' => 'big'],
            'dev_details' => ['type' => 'json'],
            'dev_comment' => ['type' => 'string'],
            'dev_last_update' => ['type' => 'datetime'],
            'dev_in_use' => ['type' => 'boolean'],
            'vendor' => ['type' => 'string'],
            'cluster_name' => ['type' => 'string'],
            'cluster_comment' => ['type' => 'string'],
            'cluster_details' => ['type' => 'json'],
            'dev_type' => ['type' => 'string'],
            'type_weight' => ['type' => 'integer'],
            'port_ip' => ['type' => 'string'],
            'port_last_update' => ['type' => 'datetime'],
            'port_comment' => ['type' => 'string'],
            'port_details' => ['type' => 'json'],
            'port_is_mng' => ['type' => 'boolean'],
            'port_mac' => ['type' => 'string'],
            'port_mask_len' => ['type' => 'integer'],
            'platform_item_details' => ['type' => 'json'],
            'platform_item_comment' => ['type' => 'string'],
            'platform_version' => ['type' => 'string'],
            'platform_inv_number' => ['type' => 'string'],
            'platform_sn' => ['type' => 'string'],
            'platform_sn_alt' => ['type' => 'string'],
            'platform' => ['type' => 'string'],
            'is_hw' => ['type' => 'boolean'],
            'software_ver' => ['type' => 'string'],
            'software_comment' => ['type' => 'string'],
            'software_details' => ['type' => 'json'],
            'software' => ['type' => 'string'],
            'module_item_details' => ['type' => 'json'],
            'module_item_comment' => ['type' => 'string'],
            'module_item_sn' => ['type' => 'string'],
            'module_item_inv_number' => ['type' => 'string'],
            'module_in_use' => ['type' => 'boolean'],
            'module_not_found' => ['type' => 'boolean'],
            'module_last_update' => ['type' => 'datetime'],
            'module' => ['type' => 'string'],
            'module_descr' => ['type' => 'string'],
        ]
    ];
    
    protected function beforeSave()
    {
        return false;
    }
}