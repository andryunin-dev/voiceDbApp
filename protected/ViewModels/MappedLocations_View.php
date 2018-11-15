<?php
/**
 * Created by IntelliJ IDEA.
 * User: karasev-dl
 * Date: 14.11.2018
 * Time: 14:49
 */

namespace App\ViewModels;


use T4\Orm\Model;

class MappedLocations_View extends Model
{
    protected static $schema = [
        'table' => 'view.mappedLotusLocations',
        'columns' => [
            'lotus_id' => ['type' => 'int'],
            'city' => ['type' => 'string'],
            'regCenter' => ['type' => 'string'],
            'region' => ['type' => 'string'],
            'office' => ['type' => 'string'],
            'comment' => ['type' => 'string'],
            'address' => ['type' => 'string'],
            'people' => ['type' => 'int'],
        ]
    ];
    
    protected function beforeSave()
    {
        return false;
    }
    
}