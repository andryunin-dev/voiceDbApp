<?php

namespace App\MappingModels;

use T4\Orm\Model;

/**
 * Class LotusLocation
 * @package App\MappingModels
 *
 * @property int $lotus_id
 * @property string $reg_center
 */
class LotusLocation extends Model
{
    protected static $schema = [
        'table' => 'mapping.lotusLocations',
        'columns' => [
            'lotus_id' => ['type' => 'int'],
            'reg_center' => ['type' => 'string']
        ]
    ];
    
    protected function beforeSave()
    {
        return false;
    }
}