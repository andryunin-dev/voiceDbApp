<?php
namespace App\Models;

use T4\Core\Exception;
use T4\Orm\Model;


class PhoneInfo extends Model
{

    /**
     * Class PhoneInfo
     * @package App\Models
     *
     * @property string $type
     * @property string $name
     * @property string $macAddress
     * @property string $prefix
     * @property string $phoneDN
     * @property string $status
     * @property string $description
     * @property string $css
     * @property string $devicePool
     * @property string $alertingName
     * @property string $partition
     *
     * @property Appliance $phone
     */
    protected static $schema = [
        'table' => 'equipment.phoneInfo',
        'columns' => [
//            'type' => ['type' => 'string'],
            'name' => ['type' => 'string'],
//            'macAddress' => ['type' => 'string'],
//            'prefix' => ['type' => 'string'],
//            'phoneDN' => ['type' => 'string'],
//            'status' => ['type' => 'string'],
//            'description' => ['type' => 'string'],
//            'css' => ['type' => 'string'],
//            'devicePool' => ['type' => 'string'],
//            'alertingName' => ['type' => 'string'],
//            'partition' => ['type' => 'string'],
        ],
//        'relations' => [
////            'phone' => ['type' => self::BELONGS_TO, 'model' => Appliance::class],
//        ]
    ];

    protected function validate()
    {
//        if (! ($this->phone instanceof Appliance)) {
//            throw new Exception('PhoneInfo: Неверный тип Phone');
//        }
//
//

    }
}
