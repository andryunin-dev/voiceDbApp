<?php

namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class ApiView_Geo
 * @package App\ViewModels
 * @property integer vrf_id
 * @property string vrf_name
 * @property string vrf_rd
 * @property string vrf_comment
 */
class ApiView_Vrfs extends Model
{
    protected static $schema = [
        'table' => 'api_view.vrfs',
        'columns' => [
            'vrf_id' => ['type' => 'int', 'length' => 'big'],
            'vrf_name' => ['type' => 'string'],
            'vrf_rd' => ['type' => 'string'],
            'vrf_comment' => ['type' => 'string'],
        ]
    ];
    
    protected function beforeSave()
    {
        return false;
    }
}