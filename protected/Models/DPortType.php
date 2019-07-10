<?php

namespace App\Models;

use function PHPSTORM_META\type;
use T4\Core\Collection;
use T4\Core\Exception;
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
    const TYPE_ETHERNET = 'Ethernet';
    const EMPTY = '';

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

    /**
     * @return DPortType
     */
    public static function getEmpty(): self
    {
        $dPortType = DPortType::findByColumn('type', self::EMPTY);
        return (false === $dPortType) ? (new self(['type' => self::EMPTY]))->save() : $dPortType;
    }

    /**
     * @param string $type
     * @return DPortType
     * @throws \T4\Core\MultiException
     */
    public static function instanceWithType(string $type): self
    {
        if (false === $dataPortType = self::findByColumn('type', $type)) {
            $dataPortType = (new self())->fill(['type' => $type])->save();
        }
        return $dataPortType;
    }
}
