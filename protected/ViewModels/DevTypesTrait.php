<?php

namespace App\ViewModels;

use App\Models\ApplianceType;
use T4\Core\Collection;

/**
 * Trait DevTypesTrait
 * @package App\ViewModels
 *
 * @property Collection|ApplianceType[] $allDevTypes
 * @property array $allDevTypes_id
 * @property Collection|ApplianceType[] $networkDevTypes
 * @property array $networkDevTypes_id
 * @property Collection|ApplianceType[] $cucmDevTypes
 * @property array $cucmDevTypes_id
 * @property Collection|ApplianceType[] $phoneDevTypes
 * @property array $phoneDevTypes_id
 */
trait DevTypesTrait
{
    protected static $networkDevs = [
        'router',
        'switch'
    ];
    protected static $phoneDevs = [
        'phone'
    ];
    protected static $cucmDevs = [
        'cmp',
        'cms'
    ];
    public static function appTypeFilter(string $filter = '') :array
    {
        switch ($filter) {
            case 'all':
                $devFilter = GeoDevModulePort_View::allDevTypes_id();
                break;
            case 'netDevices':
                $devFilter = GeoDevModulePort_View::networkDevTypes_id();
                break;
            case 'cucms':
                $devFilter = GeoDevModulePort_View::cucmDevTypes_id();
                break;
            case 'phones':
                $devFilter = GeoDevModulePort_View::phoneDevTypes_id();
                break;
            default:
                $devFilter = GeoDevModulePort_View::allDevTypes_id();
                break;
        }
        return $devFilter;
    }

    public static function applianceType_id($acc, $applianceTypeItem){
        $acc[] = $applianceTypeItem->getPk();
        return $acc;
    }

    public static function allDevTypes() :Collection
    {
        return ApplianceType::findAll();
    }
    public static function allDevTypes_id() :array
    {
        return self::allDevTypes()->reduce($carry = [], [static::class, 'applianceType_id']);
    }
    public static function networkDevTypes() :Collection
    {
        return ApplianceType::findAll()->filter(function ($applianceType) {
            return in_array($applianceType->type, self::$networkDevs);
        });
    }
    public static function networkDevTypes_id() :array
    {
        return (self::networkDevTypes())->reduce($carry = [], [static::class, 'applianceType_id']);
    }
    public static function cucmDevTypes() :Collection
    {
        return ApplianceType::findAll()->filter(function ($applianceType) {
            return in_array($applianceType->type, self::$cucmDevs);
        });
    }
    public static function cucmDevTypes_id() :array
    {
        return self::cucmDevTypes()->reduce($carry = [], [static::class, 'applianceType_id']);
    }
    public static function phoneDevTypes() :Collection
    {
        return ApplianceType::findAll()->filter(function ($applianceType) {
            return in_array($applianceType->type, self::$phoneDevs);
        });
    }
    public static function phoneDevTypes_id() :array
    {
        return self::phoneDevTypes()->reduce($carry = [], [static::class, 'applianceType_id']);
    }
}