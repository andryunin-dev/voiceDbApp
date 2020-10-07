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
    private const UNKNOWN_CITY = 'Неизвестный';

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
        if (!($this->region instanceof Region)) {
            throw new Exception('Регион не найден');
        }
        $cityFromDb = self::findByCityTitleInRegion($this->title, $this->region);
        if (false !== $cityFromDb && ($this->isNew() || $this->getPk() != $cityFromDb->getPk())) {
            throw new Exception('Город с таким именем уже существует');
        }
        return true;
    }

    /**
     * @param string $title
     * @param Region $region
     * @return mixed
     */
    public static function findByCityTitleInRegion(string $title, Region $region)
    {
        $cities = City::findAllByColumn('title', $title)
            ->filter(function ($city) use ($region) {
                return $city->region->title == $region->title;
            });
        return $cities->isEmpty() ? false : $cities->first();
    }

    /**
     * @return City
     * @throws \T4\Core\MultiException
     */
    public static function unknownCityInstance(): City
    {
        $city = self::findByColumn('title', self::UNKNOWN_CITY);
        if (false == $city) {
            $city = (new City())
                ->fill([
                    'title' => self::UNKNOWN_CITY,
                    'region' => Region::unknownRegionInstance()
                ])
                ->save();
        }
        return $city;
    }
}
