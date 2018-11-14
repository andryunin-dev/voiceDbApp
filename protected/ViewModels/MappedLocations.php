<?php
/**
 * Created by IntelliJ IDEA.
 * User: karasev-dl
 * Date: 14.11.2018
 * Time: 14:49
 */

namespace App\ViewModels;


use T4\Orm\Model;

class MappedLocations extends Model
{
    protected static $schema = [
        'table' => 'view.mapped_locations',
        'columns' => [
            'Lotus_id' => ['type' => 'int'],
            'City' => ['type' => 'string'],
            'RegCenter' => ['type' => 'string'],
            'Region' => ['type' => 'string'],
            'Office' => ['type' => 'string'],
            'Comment' => ['type' => 'string'],
            'Address' => ['type' => 'string'],
            'People' => ['type' => 'int'],
        ]
    ];
    
    protected function beforeSave()
    {
        return false;
    }
    
}