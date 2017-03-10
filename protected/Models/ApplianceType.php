<?php

namespace App\Models;


use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class ApplianceType
 * @package App\Models
 *
 * @property string $type
 * @property Collection|VoicePort[] $appliances
 */
class ApplianceType extends Model
{
    protected static $schema = [
        'table' => 'equipment.applianceTypes',
        'columns' => [
            'type' => ['type' => 'string'],
        ],
        'relations' => [
            'appliances' => ['type' => self::HAS_MANY, 'model' => Appliance::class, 'by' => '__type_id']
        ]
    ];

    public function __toString()
    {
        return $this->type;
    }


    public function validateType($type)
    {
        return (!empty(trim($type)));
    }

    public function validate()
    {
        if (
            true === empty(trim($this->type))
        ) {
            return false;
        }
        //только для нового объекта проверяем на наличие такого в БД
        if (true === $this->isNew() && false !== ApplianceType::findByColumn('type', trim($this->type))) {
            return false; //есть appliance type с таким типом
        }
        return true;
    }

}