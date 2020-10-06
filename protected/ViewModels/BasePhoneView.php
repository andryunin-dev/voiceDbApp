<?php

namespace App\ViewModels;

use T4\Orm\Model;

class BasePhoneView extends Model
{
//    use DevTypesTrait;
    use ViewHelperTrait;
    use DbaTrait;

    protected static $schema = [
        'table' => 'view.phone_base',
        'columns' => [
            'name' => ['type' => 'string'],
            'dn' => ['type' => 'string'],
            'dnDash' => ['type' => 'string'],
            'dnPrefix' => ['type' => 'string'],
            'e164msk' => ['type' => 'string'],
            'alertingName' => ['type' => 'string'],
            'depiction' => ['type' => 'string'],
            'vlanId' => ['type' => 'string'],
            'isActive' => ['type' => 'string'],
            'lastUpdate' => ['type' => 'datetime'],
            'ipAddress' => ['type' => 'string'],
            'serialNumber' => ['type' => 'string'],
            'inventoryNumber' => ['type' => 'string'],
            'platformId' => ['type' => 'int'],
            'platform' => ['type' => 'string'],
            'officeId' => ['type' => 'int'],
            'office' => ['type' => 'string'],
            'cityId' => ['type' => 'int'],
            'city' => ['type' => 'string'],
            'comment' => ['type' => 'string'],
            'switchHostname' => ['type' => 'string'],
            'switchPort' => ['type' => 'string'],
            'switchIp' => ['type' => 'string'],
            'switchInventoryNumber' => ['type' => 'string'],
            'switchPlatform' => ['type' => 'string'],
            'publisherName' => ['type' => 'string'],
            'publisherIp' => ['type' => 'string'],
        ]
    ];

    public static $columnMap = [
        'city_id' => 'cityId',
        'loc_id' => 'officeId',
        'pl_id' => 'platformId',
        'sw_hn' => 'switchHostname',
    ];

    protected static $sortOrders = [
        'default' => 'city, office, switchHostname asc',
    ];

    protected function beforeSave()
    {
        return false;
    }
}
