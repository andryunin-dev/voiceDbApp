<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class VPortType
 * @package App\Models
 *
 * @property string $type
 *
 * @property Collection|VoicePort[] $ports
 */
class VPortType extends Model
{
    protected static $schema = [
        'table' => 'equipment.voicePortTypes',
        'columns' => [
            'type' => ['type' => 'string']
        ],
        'relations' => [
            'ports' => ['type' => self::HAS_MANY, 'model' => VoicePort::class, 'by' => '__type_port_id']
        ]
    ];
    protected function validateType($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое название типа');
        }
        return true;
    }

    protected function sanitizeType($val)
    {
        return trim($val);
    }

    protected function validate()
    {
        if (false === $this->isNew()) {
            return true;
        }
        if (false !== VPortType::findByColumn('type', $this->type)) {
            throw new Exception('Такой тип существует');
        }
        return true;
    }
}