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
        if (empty($this->city)) {
            throw new Exception('Город не найден');
        }

        return true;
    }
}