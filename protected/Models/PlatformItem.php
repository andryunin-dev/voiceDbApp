<?php

namespace App\Models;

use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class PlatformItem
 * @package App\Models
 *
 * @property string $serialNumber
 * @property string $inventoryNumber
 * @property string $version
 * @property string $details
 * @property string $comment
 *
 * @property Platform $platform
 * @property Appliance $appliance
 */
class PlatformItem extends Model
{
    const NO_NUMBER = 'NO_NUMBER';

    protected static $schema = [
        'table' => 'equipment.platformItems',
        'columns' => [
            'serialNumber' => ['type' => 'string'],
            'inventoryNumber' => ['type' => 'string'],
            'version' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'platform' => ['type' => self::BELONGS_TO, 'model' => Platform::class],
            'appliance' => ['type' => self::HAS_ONE, 'model' => Appliance::class, 'by' => '__platform_item_id']
        ]
    ];

    protected function validateSerialNumber($val)
    {
        return trim($val);
    }

    protected function validateInventoryNumber($val)
    {
        return trim($val);
    }

    protected function validate()
    {
        if (false === $this->platform) {
            return false;
        }
        return true;
    }

    public static function getByPlatform(Platform $platform, string $serial)
    {
        if (empty($serial)) {
            $serial = self::NO_NUMBER;
        }

        $platformItem = self::findByPlatformSerial($platform, $serial);

        if (false == $platformItem) {
            try {
                self::getDbConnection()->beginTransaction();
                (new self())
                    ->fill([
                        'serialNumber' => $serial,
                        'platform' => $platform
                    ])
                    ->save();
                self::getDbConnection()->commitTransaction();
            } catch (MultiException $e) {
                self::getDbConnection()->rollbackTransaction();
            } catch (Exception $e) {
                self::getDbConnection()->rollbackTransaction();
            }

            return self::findByPlatformSerial($platform, $serial);
        }

        return $platformItem;
    }

    public static function findByPlatformSerial(Platform $platform, string $serial)
    {
        $query = (new Query())
            ->select()
            ->from(self::getTableName())
            ->where('"serialNumber" = :serialNumber AND "__platform_id" = :__platform_id')
            ->params([':serialNumber' => $serial, ':__platform_id' => $platform->getPk()]);

        return self::findByQuery($query);
    }
}
