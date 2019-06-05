<?php

namespace App\ViewModels;


use T4\Orm\Model;

class IpamView_Hosts extends Model
{
    const PK = 'port_id';
    protected static $schema = [
        'table' => 'ipam_view.hosts_ports',
        'columns' => []
    ];
    protected function beforeSave()
    {
        return false;
    }
}