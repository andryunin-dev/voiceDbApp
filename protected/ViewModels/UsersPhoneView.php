<?php

namespace App\ViewModels;

use T4\Orm\Model;

class UsersPhoneView extends Model
{
//    use DevTypesTrait;
    use ViewHelperTrait;
    use DbaTrait;

    protected static $schema = [
        'table' => 'view.report_phone_by_lotus_user',
        'columns' => [
            'region_id' => ['type' => 'string'],
            'region' => ['type' => 'string'],
            'office_id' => ['type' => 'string'],
            'office' => ['type' => 'string'],
            'platform_id' => ['type' => 'string'],
            'model' => ['type' => 'string'],
            'dn' => ['type' => 'string'],
            'alertingName' => ['type' => 'string'],
            'ipAddress' => ['type' => 'string'],
            'serialNumber' => ['type' => 'string'],
            'inventoryNumber' => ['type' => 'string'],
            'mol' => ['type' => 'string'],
            'inventoryUser' => ['type' => 'string'],
            'lotusUser' => ['type' => 'string'],
            'lotusUserPosition_id' => ['type' => 'string'],
            'lotusUserPosition' => ['type' => 'string'],
            'lotusUserDivision' => ['type' => 'string'],
            'lotusUserMobilePhone' => ['type' => 'string'],
            'lotusUserWorkEmail' => ['type' => 'string'],
            'switchPlatform' => ['type' => 'string'],
            'switchIp' => ['type' => 'string'],
            'switchPort' => ['type' => 'string'],
            'switchInventoryNumber' => ['type' => 'string'],
            'isActive' => ['type' => 'string'],
            'lastUpdate' => ['type' => 'datetime'],
        ]
    ];

    public static $columnMap = [
        'sw_inv' => 'switchInventoryNumber',
        'reg_id' => 'region_id',
        'loc_id' => 'office_id',
        'pl_id' => 'platform_id',
        'pos_id' => 'lotusUserPosition_id',
    ];

    protected static $sortOrders = [
        'default' => 'region, office, lotusUser asc',
    ];

    protected function beforeSave()
    {
        return false;
    }
}
