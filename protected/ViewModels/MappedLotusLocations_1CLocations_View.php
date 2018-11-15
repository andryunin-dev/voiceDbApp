<?php
/**
 * Created by IntelliJ IDEA.
 * User: karasev-dl
 * Date: 15.11.2018
 * Time: 11:42
 */

namespace App\ViewModels;


use T4\Orm\Model;

class MappedLotusLocations_1CLocations_View extends Model
{
    protected static $schema = [
        'table' => 'view.mappedLotusLoc_1CLocations',
        'columns' => [
            'lotus_id' => ['type' => 'int'],
            'city' => ['type' => 'string'],
            'regCenter' => ['type' => 'string'],
            'region' => ['type' => 'string'],
            'office' => ['type' => 'string'],
            'comment' => ['type' => 'string'],
            'address' => ['type' => 'string'],
            'people' => ['type' => 'int'],
            'flatCode' => ['type' => 'int'],
            'flatAddress' => ['type' => 'string'],
        ]
    ];
    
    protected function beforeSave()
    {
        return false;
    }
}