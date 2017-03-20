<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class City
 * @package App\Models
 *
 * @property string $title
 * @property string $diallingCode
 *
 * @property Region $region
 * @property Collection|Address[] $addresses
 */
class City extends Model
{
    protected static $schema = [
        'table' => 'geolocation.cities',
        'columns' => [
            'title' => ['type' => 'string'],
            'diallingCode' => ['type' => 'string']
        ],
        'relations' => [
            'region' => ['type' => self::BELONGS_TO, 'model' => Region::class],
            'addresses' => ['type' => self::HAS_MANY, 'model' => Address::class]
        ]
    ];

    protected function validateTitle($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое имя города');
        }
        return true;
    }

    protected function sanitizeTitle($val)
    {
        return trim($val);
    }

    protected function validate()
    {
        if (false === $this->region) {
            throw new Exception('Регион не найден');
        }
        if (false === $this->isNew()) {
            return true;
        }
        if (false !== City::findByColumn('title', $this->title)) {
            throw new Exception('Город с таким именем существует');
        }
        return true;
    }
}