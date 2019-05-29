<?php
/**
 * Created by IntelliJ IDEA.
 * User: karasev-dl
 * Date: 16.05.2019
 * Time: 17:32
 */

namespace App\ViewModels;


use T4\Orm\Model;

class IpamView_Networks extends Model
{
    const PK = 'net_id';
    protected static $schema = [
        'table' => 'ipam_view.nets',
        'columns' => [
            'net_id' => ['type' => 'int', 'length' => 'big'],
            'net_ip' => ['type' => 'text'],
            'net_mask' => ['type' => 'text'],
            'vrf_id' => ['type' => 'int', 'length' => 'big'],
            'vrf_name' => ['type' => 'text'],
            'vrf_rd' => ['type' => 'text'],
            'vrf_comment' => ['type' => 'text'],
            'net_children' => ['type' => 'text'],
            'host_children' => ['type' => 'text'],
            'net_location' => ['type' => 'json'],
            'bgp_as' => ['type' => 'int'],
        ]
    ];
    protected function beforeSave()
    {
        return false;
    }
}