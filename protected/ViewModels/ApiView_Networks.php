<?php

namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class ApiView_Networks
 * @package App\ViewModels
 *
 * @property int $net_id
 * @property int $vrf_id
 * @property string $net_ip
 * @property string $net_comment
 * @property string $vrf_rd
 * @property string $vrf_name
 * @property string $vrf_comment
 */
class ApiView_Networks extends Model
{
    const PK = 'net_id';
    protected static $schema = [
        'table' => 'api_view.networks',
        'columns' => [
            'vrf_id' => ['type' => 'int', 'length' => 'big'],
            'net_ip' => ['type' => 'text'],
            'net_comment' => ['type' => 'text'],
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