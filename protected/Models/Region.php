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
    private const UNKNOWN_REGION = 'Неизвестный';

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
        $regionFromDb = Region::findByColumn('title', $this->title);
        if (false != $regionFromDb && ($this->isNew() || $this->getPk() != $regionFromDb->getPk())) {
            throw new Exception('Регион с таким именем уже существует');
        }
        return true;
    }

    /**
     * @return Region
     * @throws \T4\Core\MultiException
     */
    public static function unknownRegionInstance(): Region
    {
        $region = self::findByColumn('title', self::UNKNOWN_REGION);
        if (false == $region) {
            $region = (new Region())->fill(['title' => self::UNKNOWN_REGION])->save();
        }
        return $region;
    }
}
