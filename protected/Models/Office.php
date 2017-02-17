<?php

namespace App\Models;

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

    protected function validate()
    {
        if (false === $this->isNew()) {
            if (empty(trim($this->lotusId))) {
                return false; //LotusId обязательное поле
            }
            if (empty(trim($this->title))) {
                return false; //Пустое название офиса
            }
            return true;
        }

        if (true == Office::findByColumn('lotusId', trim($this->lotusId))) {
            return false; //есть офис с таким Lotus ID
        }
        if (true == Office::findByColumn('title', $this->title)) {
            return false; //Офис с таким названием уже есть
        }
        return true;
    }

}