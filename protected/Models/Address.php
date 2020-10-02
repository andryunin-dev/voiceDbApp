<?php

namespace App\Models;

use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class Address
 * @package App\Models
 *
 * @property string $address
 * @property City $city
 * @property Office $office
 * @property PartnerOffice $partnerOffice
 */
class Address extends Model
{
    private const UNKNOWN_ADDRESS = 'Неизвестный';

    protected static $schema = [
        'table' => 'geolocation.addresses',
        'columns' => [
            'address' => ['type' => 'text']
        ],
        'relations' => [
            'city' => ['type' => self::BELONGS_TO, 'model' => City::class],
            'office' => ['type' => self::HAS_ONE, 'model' => Office::class],
            'partnerOffice' => ['type' => self::HAS_ONE, 'model' => PartnerOffice::class]
        ]
    ];

    protected function validateAddress($val)
    {
        return true;
    }

    protected function sanitizeAddress($val)
    {
        return trim($val);
    }

    protected function validate()
    {
        if (!($this->city instanceof City)) {
            throw new Exception('Город не найден');
        }
        $addressFromDb = self::findByAddressInCity($this->address, $this->city);
        if (false !== $addressFromDb && ($this->isNew() || $this->getPk() != $addressFromDb->getPk())) {
            throw new \Exception('В городе уже есть такой адрес');
        }
        return true;
    }

    /**
     * @param string $address
     * @param City $city
     * @return Address|false
     */
    public static function findByAddressInCity(string $address, City $city)
    {
        $addresses = Address::findAllByColumn('address', $address)
            ->filter(function ($addressFromDb) use ($city) {
                return $addressFromDb->city->title == $city->title;
            });
        return $addresses->isEmpty() ? false : $addresses->first();
    }

    /**
     * @return Address
     * @throws \T4\Core\MultiException
     */
    public static function unknownAddressInstance(): Address
    {
        $address = self::findByColumn('address', self::UNKNOWN_ADDRESS);
        if (false == $address) {
            $address = (new Address())
                ->fill([
                    'address' => self::UNKNOWN_ADDRESS,
                    'city' => City::unknownCityInstance()
                ])
                ->save();
        }
        return $address;
    }
}
