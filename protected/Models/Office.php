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
    private const VIRTUAL_APPS_LOTUS_ID = 1998;
    private const UNKNOWN_LOCATION_LOTUS_ID = 1999;

    protected static $schema = [
        'table' => 'company.offices',
        'columns' => [
            'title' => ['type' => 'string'],
            'lotusId' => ['type' => 'integer'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'string'],
            'isCCO' => ['type' => 'boolean']
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
        if (!($this->address instanceof Address)) {
            throw new Exception('Ошибка при записи адреса. Возможно не найден город');
        }
        if (!($this->status instanceof OfficeStatus)) {
            throw new Exception('Статус не найден');
        }
        $officeFromDb = Office::findByColumn('lotusId', $this->lotusId);
        if ($officeFromDb !== false && ($this->isNew() || $this->getPk() != $officeFromDb->getPk())) {
            throw new Exception('Офис с данным Lotus ID уже существует');
        }
        $officeFromDb = Office::findByColumn('title', $this->title);
        if ($officeFromDb !== false && ($this->isNew() || $this->getPk() != $officeFromDb->getPk())) {
            throw new Exception('Офис с таким названием уже существует');
        }
        return true;
    }

    protected function getPeople()
    {
        return LotusLocation::employeesByLotusId($this->lotusId);
    }

    /**
     * @return Office
     * @throws MultiException
     */
    public static function unknownLocationInstance(): Office
    {
        $office = Office::findByColumn('lotusId', self::UNKNOWN_LOCATION_LOTUS_ID);
        if (false == $office) {
            $office = (new Office())
                ->fill([
                    'title' => 'Неизвестный',
                    'lotusId' => self::UNKNOWN_LOCATION_LOTUS_ID,
                    'isCCO' => false,
                    'address' => Address::unknownAddressInstance(),
                    'status' => OfficeStatus::openInstance()
                ])
                ->save();
        }
        return $office;
    }

    /**
     * @return Office
     * @throws MultiException
     */
    public static function virtualAppliancesInstance(): Office
    {
        $office = Office::findByColumn('lotusId', self::VIRTUAL_APPS_LOTUS_ID);
        if (false == $office) {
            $office = (new Office())
                ->fill([
                    'title' => 'Виртуальные устройства',
                    'lotusId' => self::VIRTUAL_APPS_LOTUS_ID,
                    'isCCO' => false,
                    'address' => Address::unknownAddressInstance(),
                    'status' => OfficeStatus::openInstance()
                ])
                ->save();
        }
        return $office;
    }
}
