<?php

namespace App\Models;

use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Dbal\Query;
use T4\Orm\Model;


class PhoneInfo extends Model
{
    protected static $schema = [
        'table' => 'equipment.phoneInfo',
        'columns' => [
            'cmName' => ['type' => 'string'],
            'cmIpAddress' => ['type' => 'string'],
            'Name' => ['type' => 'string'],
            'IpAddress' => ['type' => 'string'],
            'Description' => ['type' => 'string'],
            'Status' => ['type' => 'string'],
            'css' => ['type' => 'string'],
            'name_off_clause' => ['type' => 'string'],
            'dpool' => ['type' => 'string'],
            'prefix' => ['type' => 'string'],
            'dnorpattern' => ['type' => 'string'],
            'fio' => ['type' => 'string'],
            'pt' => ['type' => 'string'],
            'type' => ['type' => 'string'],
            'MACAddress' => ['type' => 'string'],
            'phoneDN' => ['type' => 'string'],
        ],
        'relations' => [
            'platform' => ['type' => self::BELONGS_TO, 'model' => Platform::class],
            'appliance' => ['type' => self::HAS_ONE, 'model' => Appliance::class, 'by' => '__platform_item_id']
        ]
    ];

    /**
     * @return bool
     * @throws Exception
     */
    protected function validate()
    {
        if (! ($this->platform instanceof Platform)) {
            throw new Exception('PlatformItem: Неверный тип Platform');
        }

        if (empty(trim($this->serialNumber))) {
            return true;
        }

        $platformItem = PlatformItem::findByPlatformSerial($this->platform, $this->serialNumber);

        if (true === $this->isNew && ($platformItem instanceof PlatformItem)) {
            throw new Exception('Такой PlatformItem уже существует');
        }

        if (true === $this->isUpdated && ($platformItem instanceof PlatformItem) && ($platformItem->getPk() != $this->getPk())) {
            throw new Exception('Такой PlatformItem уже существует');
        }

        return true;
    }

    /**
     * @param Platform $platform
     * @param $serialNumber
     * @return PlatformItem|bool
     */
    public static function findByPlatformSerial(Platform $platform, $serialNumber)
    {
        return $platform->platformItems->filter(
            function ($platformItem) use ($serialNumber) {
                return $serialNumber == $platformItem->serialNumber;
            }
        )->first();
    }
}
