<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Orm\Model;

/**
 * Class DPortType
 * @package App\Models
 *
 * @property string $type
 *
 * @property Collection|DataPort[] $ports
 */
class DPortType extends Model
{
    protected static $schema = [
        'table' => 'equipment.dataPortTypes',
        'columns' => [
            'type' => ['type' => 'string']
        ],
        'relations' => [
            'ports' => ['type' => self::HAS_MANY, 'model' => DataPort::class, 'by' => '__type_port_id']
        ]
    ];

    protected function validateType($val)
    {
//        if (empty(trim($val))) {
//            throw new Exception('Пустое название DataPortType');
//        }

        return true;
    }

    protected function validate()
    {
        $portType = DPortType::findByColumn('type', $this->type);

        if (true === $this->isNew && ($portType instanceof DPortType)) {
            throw new Exception('Такой DataPortType уже существует');
        }

        if (true === $this->isUpdated && ($portType instanceof DPortType) && ($portType->getPk() != $this->getPk())) {
            throw new Exception('Такой DataPortType уже существует');
        }

        return true;
    }

    public static function getEmpty()
    {
        return self::findByColumn('type','');
    }
}
