<?php

namespace App\Models;

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
}