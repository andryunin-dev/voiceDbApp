<?php

namespace App\Storage1CModels;

use App\Models\Appliance;
use T4\Dbal\QueryBuilder;
use T4\Orm\Exception;
use T4\Orm\Model;

/**
 * Class Appliance1C
 * @package App\Storage1CModels
 *
 * @property InventoryItem1C $inventoryData
 * @property Appliance $voiceAppliance
 */
class Appliance1C extends Model
{
    private static $inventoryDataColumn = '__inventory_item_id';
    private static $voiceApplianceColumn = '__voice_appliance_id';

    protected static $schema = [
        'table' => 'storage_1c.appliances1C',
        'columns' => [],
        'relations' => [
            'inventoryData' => ['type' => self::BELONGS_TO, 'model' => InventoryItem1C::class, 'by' => '__inventory_item_id'],
            'voiceAppliance' => ['type' => self::BELONGS_TO, 'model' => Appliance::class, 'by' => '__voice_appliance_id'],
        ],
    ];


    /**
     * @return bool
     * @throws Exception
     */
    protected  function validate(): bool
    {
        if (!($this->inventoryData instanceof InventoryItem1C)) {
            throw new Exception('Not a valid Appliance1C\'s inventoryData type');
        }
        if (!is_null($this->voiceAppliance) && !($this->voiceAppliance instanceof Appliance)) {
            throw new Exception('Not a valid Appliance1C\'s voiceAppliance type');
        }

        if (true === $this->isNew()) {
            $duplicateAppliance1CByInventoryData = self::findByInventoryData($this->inventoryData);
            if (false !== $duplicateAppliance1CByInventoryData) {
                throw new Exception('A Appliance1C with this inventoryData exists');
            }

            if (!is_null($this->voiceAppliance)) {
                $duplicateAppliance1CByVoiceAppliance = self::findByVoiceAppliance($this->voiceAppliance);
                if (false !== $duplicateAppliance1CByVoiceAppliance) {
                    throw new Exception('A Appliance1C with this voiceAppliance exists');
                }
            }
        }

        if (false === $this->isNew()) {
            $duplicateAppliance1CByInventoryData = self::findByInventoryData($this->inventoryData);
            if (false !== $duplicateAppliance1CByInventoryData && $duplicateAppliance1CByInventoryData->getPk() != $this->getPk()) {
                throw new Exception('A Appliance1C with this inventoryData exists');
            }

            if (!is_null($this->voiceAppliance)) {
                $duplicateAppliance1CByVoiceAppliance = self::findByVoiceAppliance($this->voiceAppliance);
                if (false !== $duplicateAppliance1CByVoiceAppliance && $duplicateAppliance1CByVoiceAppliance->getPk() != $this->getPk()) {
                    throw new Exception('A Appliance1C with this voiceAppliance exists');
                }
            }
        }

        $duplicateModule1CByInventoryData = Module1C::findByInventoryData($this->inventoryData);
        if (false !== $duplicateModule1CByInventoryData) {
            throw new Exception('This inventoryData is bind with the Module1C');
        }

        if (InventoryItemCategory::APPLIANCE != $this->inventoryData->category->title) {
            throw new Exception('Not a valid Appliance1C\'s category type');
        }
        return true;
    }

    /**
     * @param InventoryItem1C $inventoryItem1C
     * @param Appliance $voiceAppliance
     * @return static|false
     */
    public static function findByInventoryDataAndVoiceAppliance(InventoryItem1C $inventoryItem1C, Appliance $voiceAppliance)
    {
        $query = (new QueryBuilder())
            ->select()
            ->from(self::getTableName())
            ->where( self::$inventoryDataColumn.' = :i AND '.self::$voiceApplianceColumn.' = :a')
            ->params([':i' => $inventoryItem1C->getPk(), ':a' => $voiceAppliance->getPk()]);
        return self::findByQuery($query);
    }

    /**
     * @param InventoryItem1C $inventoryItem1C
     * @return static|false
     */
    public static function findByInventoryData(InventoryItem1C $inventoryItem1C)
    {
        return self::findByColumn(self::$inventoryDataColumn, $inventoryItem1C->getPk());
    }

    /**
     * @param Appliance $voiceAppliance
     * @return static|false
     */
    public static function findByVoiceAppliance(Appliance $voiceAppliance)
    {
        return self::findByColumn(self::$voiceApplianceColumn, $voiceAppliance->getPk());
    }
}
