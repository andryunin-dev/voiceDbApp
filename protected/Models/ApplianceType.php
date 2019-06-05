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
 * @property integer $sortOrder
 * @property Collection|VoicePort[] $appliances
 */
class ApplianceType extends Model
{
    const CUCM_PUBLISHER = 'cmp';
    const PHONE = 'phone';
    const ROUTER = 'router';
    const SWITCH = 'switch';

    protected static $schema = [
        'table' => 'equipment.applianceTypes',
        'columns' => [
            'type' => ['type' => 'string'],
            'sortOrder' => ['type' => 'int'],
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

    protected function sanitizeSortOrder($val)
    {
        if (is_numeric($val)) {
            return (int)$val;
        } else {
            return 0;
        }
    }

    protected function validate()
    {
        $applianceType = ApplianceType::findByColumn('type', $this->type);

        if (true === $this->isNew && ($applianceType instanceof ApplianceType)) {
            throw new Exception('Такой ApplianceType уже существует');
        }

        if (true === $this->isUpdated && ($applianceType instanceof ApplianceType) && ($applianceType->getPk() != $this->getPk())) {
            throw new Exception('Такой ApplianceType уже существует');
        }

        return true;
    }

    public static function getInstanceByType(string $type)
    {
        $applianceType = self::findByColumn('type', $type);
        if (false === $applianceType) {
            $applianceType = (new self())->fill(['type' => $type])->save();
        }
        return $applianceType;
    }
}
