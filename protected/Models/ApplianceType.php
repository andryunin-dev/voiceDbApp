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
    const NO_TYPE = 'NO_TYPE';

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
        if (empty($type)) {
            $type = self::NO_TYPE;
        }

        $applianceType = self::findByType($type);

        if (false == $applianceType) {
            try {
                self::getDbConnection()->beginTransaction();
                (new self())
                    ->fill([
                        'type' => $type
                    ])
                    ->save();
                self::getDbConnection()->commitTransaction();
            } catch (MultiException $e) {
                self::getDbConnection()->rollbackTransaction();
            } catch (Exception $e) {
                self::getDbConnection()->rollbackTransaction();
            }

            return self::findByType($type);
        }

        return $applianceType;
    }
}