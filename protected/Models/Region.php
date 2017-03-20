<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class Region
 * @package App\Models
 *
 * @property string $title Region's title
 *
 * @property Collection|City[] $cities
 */
class Region extends Model
{
    protected static $schema = [
        'table' => 'geolocation.regions',
        'columns' => [
            'title' => ['type' => 'string']
        ],
        'relations' => [
            'cities' => ['type' => self::HAS_MANY, 'model' => City::class]
        ]
    ];

    protected function validateTitle($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое имя региона');
        }
        return true;
    }

    protected function sanitizeTitle($val)
    {
        return trim($val);
    }

    protected function validate()
    {
        if (false === $this->isNew()) {
            return true;
        }
        if (false !== Region::findByColumn('title', $this->title)) {
            throw new Exception('Регион с таким именем существует');
        }
        return true;
    }
}