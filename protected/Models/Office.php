<?php

namespace App\Models;

use App\Storage1CModels\Rooms1C;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Orm\Model;

/**
 * Class Office
 * @package App\Models
 *
 * @property string $title
 * @property int $lotusId
 * @property string $details
 * @property string $comment
 *
 * @property Address $address
 * @property OfficeStatus $status
 * @property Appliance $appliances
 *
 * @property int $people //кол-во сотрудников в офисе согласно Лотус базе
 * @property Rooms1C $rooms1C   // помещение в базе 1С, соответствующее офису в Лотус базе
 */
class Office extends Model
{
    protected static $schema = [
        'table' => 'company.offices',
        'columns' => [
            'title' => ['type' => 'string'],
            'lotusId' => ['type' => 'integer'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'address' => ['type' => self::BELONGS_TO, 'model' => Address::class],
            'status' => ['type' => self::BELONGS_TO, 'model' => OfficeStatus::class, 'on' => '__office_status_id'],
            'appliances' => ['type' => self::HAS_MANY, 'model' => Appliance::class, 'by' => '__location_id'],
            'rooms1C' => ['type' => self::HAS_MANY, 'model' => Rooms1C::class, 'by' => '__voice_office_id'],
        ]
    ];

    protected function validateTitle($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое название офиса');
        }
        return true;
    }

    protected function sanitizeTitle($val)
    {
        return trim($val);
    }

    protected function validateLotusId($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустой LotusId');
        }
        if (!is_numeric(trim($val))) {
            throw new Exception('Lotus ID должен состоять только из цифр');
        }
        return true;
    }

    protected function sanitizeLotusId($val)
    {
        return trim($val);
    }

    protected function validate()
    {
        if (empty($this->address)) {
            throw new Exception('Ошибка при записи адреса. Возможно не найден город');
        }
        if (empty($this->status)) {
            throw new Exception('Статус не найден');
        }
        //TODO: здесь нужно вставить проверку при изменении LotusId
        if (false === $this->isNew()) {
            return true;
        }
        //дальнейшие проверки только для новых офисов
        //проверка существования офиса по lotusId (только для новых офисов)
        if (false !== Office::findByColumn('lotusId', $this->lotusId)) {
            throw new Exception('Офис с данным Lotus ID существует');
        }
        //проверка существования офиса по title
        if (false !== Office::findByColumn('title', $this->title)) {
            throw new Exception('Офис с таким названием существует');
        }

        return true;
    }
    protected function getPeople()
    {
        return LotusLocation::employeesByLotusId($this->lotusId);
    }
}
