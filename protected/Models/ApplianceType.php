<?php

namespace App\Models;


use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
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

    protected function validateType($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое название типа(роли)');
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
        if (false !== ApplianceType::findByColumn('type', $this->type)) {
            throw new Exception('Такой тип уже существует');
        }
        return true;
    }

    public static function getByType(string $type)
    {
        $applianceType = self::findByType($type);

        if (false == $applianceType) {
            $applianceType = (new self())
                ->fill([
                    'type' => $type
                ])
                ->save();
        }

        return $applianceType;
    }
}

