<?php
/**
 * Created by IntelliJ IDEA.
 * User: karasev-dl
 * Date: 14.11.2018
 * Time: 14:49
 */

namespace App\ViewModels;


use T4\Orm\Model;

/**
 * Class MappedLocations_View
 * @package App\ViewModels
 *
 * @property int $lotus_id
 * @property string $city
 * @property string $regCenter
 * @property string $region
 * @property string $office
 * @property string $comment
 * @property string $address
 * @property string $people
 */

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