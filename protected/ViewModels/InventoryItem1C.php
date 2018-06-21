<?php

namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class InventoryItem1C
 * @package App\ViewModels
 *
 * @property int $invItem_id
 * @property string $invItem_inventoryNumber
 * @property string $invItem_serialNumber
 * @property \DateTime $invItem_dateOfRegistration
 * @property \DateTime $invItem_lastUpdate
 * @property int $mol_id
 * @property string $mol_fio
 * @property int $mol_tabNumber
 * @property int $nomenclature1C_id
 * @property string $nomenclature1C_title
 * @property int $nomenclatureType_id
 * @property string $nomenclatureType_type
 * @property int $invItemCategory_id
 * @property string $invItemCategory_title
 * @property int $rooms1C_id
 * @property string $rooms1C_roomsCode
 * @property string $rooms1C_address
 * @property string $rooms1C_title
 * @property int $office_id
 * @property int $office_lotusId
 * @property int $roomsTypes_id
 * @property string $roomsTypes_type
 * @property int $city1C_id
 * @property string $city1C_title
 * @property int $region1C_id
 * @property string $region1C_title
 */
class InventoryItem1C extends Model
{
    protected static $schema = [
        'table' => 'view.inventory_item1c',
        'columns' => [
            'invItem_id' => ['type' => 'int', 'length' => 'big'],
            'invItem_inventoryNumber' => ['type' => 'string'],
            'invItem_serialNumber' => ['type' => 'string'],
            'invItem_dateOfRegistration' => ['type' => 'datetime'],
            'invItem_lastUpdate' => ['type' => 'datetime'],
            'mol_id' => ['type' => 'int', 'length' => 'big'],
            'mol_fio' => ['type' => 'string'],
            'mol_tabNumber' => ['type' => 'int'],
            'nomenclature1C_id' => ['type' => 'int', 'length' => 'big'],
            'nomenclature1C_title' => ['type' => 'text'],
            'nomenclatureType_id' => ['type' => 'int', 'length' => 'big'],
            'nomenclatureType_type' => ['type' => 'string'],
            'invItemCategory_id' => ['type' => 'int', 'length' => 'big'],
            'invItemCategory_title' => ['type' => 'string'],
            'rooms1C_id' => ['type' => 'int', 'length' => 'big'],
            'rooms1C_roomsCode' => ['type' => 'string'],
            'rooms1C_address' => ['type' => 'text'],
            'rooms1C_title' => ['type' => 'text'],
            'office_id' => ['type' => 'int', 'length' => 'big'],
            'office_lotusId' => ['type' => 'int'],
            'roomsTypes_id' => ['type' => 'int', 'length' => 'big'],
            'roomsTypes_type' => ['type' => 'string'],
            'city1C_id' => ['type' => 'int', 'length' => 'big'],
            'city1C_title' => ['type' => 'string'],
            'region1C_id' => ['type' => 'int', 'length' => 'big'],
            'region1C_title' => ['type' => 'string'],
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }
}
