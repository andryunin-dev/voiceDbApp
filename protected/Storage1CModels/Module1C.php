<?php

namespace App\Storage1CModels;

use App\Models\ModuleItem;
use T4\Dbal\QueryBuilder;
use T4\Orm\Exception;
use T4\Orm\Model;

/**
 * Class Module1C
 * @package App\Storage1CModels
 *
 * @property InventoryItem1C $inventoryData
 * @property ModuleItem $voiceModule
 */
class Module1C extends Model
{
    private static $inventoryDataColumn = '__inventory_item_id';
    private static $voiceModuleColumn = '__voice_module_id';

    protected static $schema = [
        'table' => 'storage_1c.modules1C',
        'columns' => [],
        'relations' => [
            'inventoryData' => ['type' => self::BELONGS_TO, 'model' => InventoryItem1C::class, 'by' => '__inventory_item_id'],
            'voiceModule' => ['type' => self::BELONGS_TO, 'model' => ModuleItem::class, 'by' => '__voice_module_id'],
        ],
    ];


    /**
     * @return bool
     * @throws Exception
     */
    protected  function validate(): bool
    {
        if (!($this->inventoryData instanceof InventoryItem1C)) {
            throw new Exception('Not a valid Module1C\'s inventoryData type');
        }
        if (!($this->voiceModule instanceof ModuleItem) && !is_null($this->voiceModule)) {
            throw new Exception('Not a valid Module1C\'s voiceModule type');
        }

        if (true === $this->isNew()) {
            $duplicateModule1CByInventoryData = self::findByInventoryData($this->inventoryData);
            if (false !== $duplicateModule1CByInventoryData) {
                throw new Exception('A Module1C with this inventoryData exists');
            }

            if (!is_null($this->voiceModule)) {
                $duplicateModule1CByVoiceModule = self::findByVoiceModule($this->voiceModule);
                if (false !== $duplicateModule1CByVoiceModule) {
                    throw new Exception('A Module1C with this voiceModule exists');
                }
            }
        }

        if (false === $this->isNew()) {
            $duplicateModule1CByInventoryData = self::findByInventoryData($this->inventoryData);
            if (false !== $duplicateModule1CByInventoryData && $duplicateModule1CByInventoryData->getPk() != $this->getPk()) {
                throw new Exception('A Module1C with this inventoryData exists');
            }

            if (!is_null($this->voiceModule)) {
                $duplicateModule1CByVoiceModule = self::findByVoiceModule($this->voiceModule);
                if (false !== $duplicateModule1CByVoiceModule && $duplicateModule1CByVoiceModule->getPk() != $this->getPk()) {
                    throw new Exception('A Module1C with this voiceModule exists');
                }
            }
        }

        $duplicateAppliance1CByInventoryData = Appliance1C::findByInventoryData($this->inventoryData);
        if (false !== $duplicateAppliance1CByInventoryData) {
            throw new Exception('This inventoryData is bind with the Appliance1C');
        }

        if (InventoryItemCategory::MODULE != $this->inventoryData->category->title) {
            throw new Exception('Not a valid Module1C\'s category type');
        }
        return true;
    }

    /**
     * @param InventoryItem1C $inventoryItem1C
     * @param ModuleItem $voiceModule
     * @return static|false
     */
    public static function findByInventoryDataAndVoiceModule(InventoryItem1C $inventoryItem1C, ModuleItem $voiceModule)
    {
        $query = (new QueryBuilder())
            ->select()
            ->from(self::getTableName())
            ->where( self::$inventoryDataColumn.' = :i AND '.self::$voiceModuleColumn.' = :m')
            ->params([':i' => $inventoryItem1C->getPk(), ':m' => $voiceModule->getPk()]);
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
     * @param ModuleItem $voiceModule
     * @return static|false
     */
    public static function findByVoiceModule(ModuleItem $voiceModule)
    {
        return self::findByColumn(self::$voiceModuleColumn, $voiceModule->getPk());
    }
}
