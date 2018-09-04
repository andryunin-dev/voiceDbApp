<?php

namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class Networks_View
 * @package App\ViewModels
 *
 * @property int $net_id
 * @property string $address
 * @property string $netmask
 * @property string $comment
 * @property int $vrf_id
 * @property string $vrf_name
 * @property string $vrf_rd
 */
class NetworksView extends Model
{
    const PK = 'netId';
    protected static $schema = [
        'table' => 'view.networks',
        'columns' => [
            'netId' => ['type' => 'int', 'length' => 'big'],
            'address' => ['type' => 'text'],
            'netmask' => ['type' => 'text'],
            'comment' => ['type' => 'text'],
            'vrfId' => ['type' => 'int', 'length' => 'big'],
            'vrfName' => ['type' => 'text'],
            'vrfRd' => ['type' => 'text'],
        ]
    ];
    protected function beforeSave()
    {
        return false;
    }
}