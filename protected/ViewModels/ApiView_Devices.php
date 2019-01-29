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
            'platform_id' => ['type' => 'int', 'length' => 'big'],
            'software_id' => ['type' => 'int', 'length' => 'big'],
            'software_vendor_id' => ['type' => 'int', 'length' => 'big'],
            'platform_vendor_id' => ['type' => 'int', 'length' => 'big'],
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
            'platform_item_details' => ['type' => 'json'],
            'platform_item_comment' => ['type' => 'string'],
            'platform_item_version' => ['type' => 'string'],
            'platform_item_inv_number' => ['type' => 'string'],
            'platform_item_sn' => ['type' => 'string'],
            'platform_item_sn_alt' => ['type' => 'string'],
            'platform' => ['type' => 'string'],
            'platform_vendor' => ['type' => 'string'],
            'is_hw' => ['type' => 'boolean'],
            'software' => ['type' => 'string'],
            'software_vendor' => ['type' => 'string'],
            'software_item_ver' => ['type' => 'string'],
            'software_item_comment' => ['type' => 'string'],
            'software_item_details' => ['type' => 'json'],
        ]
    ];
    
    protected function beforeSave()
    {
        return false;
    }
}