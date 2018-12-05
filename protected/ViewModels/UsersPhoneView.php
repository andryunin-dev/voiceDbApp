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
            'region' => ['type' => 'string'],
            'office' => ['type' => 'string'],
            'model' => ['type' => 'string'],
            'dn' => ['type' => 'string'],
            'alertingName' => ['type' => 'string'],
            'ipAddress' => ['type' => 'string'],
            'serialNumber' => ['type' => 'string'],
            'inventoryNumber' => ['type' => 'string'],
            'mol' => ['type' => 'string'],
            'inventoryUser' => ['type' => 'string'],
            'lotusUser' => ['type' => 'string'],
            'lotusUserPosition' => ['type' => 'string'],
            'lotusUserDivision' => ['type' => 'string'],
            'lotusUserMobilePhone' => ['type' => 'string'],
            'switchPlatform' => ['type' => 'string'],
            'switchIp' => ['type' => 'string'],
            'switchPort' => ['type' => 'string'],
            'switchInventoryNumber' => ['type' => 'string'],
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }

    public static $columnMap = [];

    protected static $sortOrders = [
        'default' => 'region, office, lotusUser asc',
    ];
}
