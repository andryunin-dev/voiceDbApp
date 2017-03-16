<?php

namespace App\Models;

use T4\Core\Exception;
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
            'status' => ['type' => self::BELONGS_TO, 'model' => OfficeStatus::class, 'on' => '__office_status_id']
        ]
    ];

    protected function validateTitle($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое Название офиса');
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
        //проверка существования офиса по lotusId
        if (false !== Office::findByColumn('lotusId', $this->lotusId)) {
            throw new Exception('Офис с данным Lotus ID существует');
        }
        //проверка существования офиса по title
        if (false !== Office::findByColumn('title', $this->title)) {
            throw new Exception('Офис с таким названием существует');
        }

        return true;
    }
}