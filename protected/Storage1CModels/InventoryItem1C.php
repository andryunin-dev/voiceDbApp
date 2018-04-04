<?php

namespace App\Storage1CModels;

use T4\Orm\Exception;
use T4\Orm\Model;
use T4\Validation\Validators\DateTime;

/**
 * Class InventoryItem1C
 * @package App\Storage1CModels
 *
 * @property string $inventoryNumber
 * @property string $serialNumber
 * @property DateTime $dateOfRegistration
 * @property DateTime $lastUpdate
 * @property InventoryItemCategory $category
 * @property Nomenclature1C $nomenclature
 * @property Mol $mol
 * @property Rooms1C $rooms1C
 */
class InventoryItem1C extends Model
{
    private const EMPTY = '';

    protected static $schema = [
        'table' => 'storage_1c.inventoryItem1C',
        'columns' => [
            'inventoryNumber' => ['type' => 'string'],
            'serialNumber' => ['type' => 'string'],
            'dateOfRegistration' => ['type' => 'datetime'],
            'lastUpdate' => ['type' => 'datetime'],
        ],
        'relations' => [
            'category' => ['type' => self::BELONGS_TO, 'model' => InventoryItemCategory::class, 'by' => '__category_id'],
            'nomenclature' => ['type' => self::BELONGS_TO, 'model' => Nomenclature1C::class, 'by' => '__nomenclature_id'],
            'mol' => ['type' => self::BELONGS_TO, 'model' => Mol::class, 'by' => '__mol_id'],
            'rooms1C' => ['type' =>self::BELONGS_TO, 'model' => Rooms1C::class, 'by' => '__rooms_1c_id'],
        ],
    ];


    /**
     * @return bool
     * @throws Exception
     */
    protected function validate(): bool
    {
        if (!is_null($this->dateOfRegistration) && !($this->dateOfRegistration instanceof DateTime)) {
            throw new Exception('Not a valid InventoryItem1C\'s dateOfRegistration type');
        }
        if (!($this->lastUpdate instanceof DateTime)) {
            throw new Exception('Not a valid InventoryItem1C\'s lastUpdate type');
        }
        if (!($this->category instanceof InventoryItemCategory)) {
            throw new Exception('Not a valid InventoryItem1C\'s category type');
        }
        if (!($this->nomenclature instanceof Nomenclature1C)) {
            throw new Exception('Not a valid InventoryItem1C\'s nomenclature type');
        }
        if (!($this->mol instanceof Mol)) {
            throw new Exception('Not a valid InventoryItem1C\'s mol type');
        }
        if (!($this->rooms1C instanceof Rooms1C)) {
            throw new Exception('Not a valid InventoryItem1C\'s rooms1C type');
        }

        $dyplicateByInventoryNumber = self::findByColumn('inventoryNumber', $this->inventoryNumber);

        if (true === $this->isNew() && false !== $dyplicateByInventoryNumber) {
            throw new Exception('A InventoryItem1C with this inventoryNumber exists');
        }
        if (false === $this->isNew() && false !== $dyplicateByInventoryNumber && $dyplicateByInventoryNumber->getPk() != $this->getPk()) {
            throw new Exception('A InventoryItem1C with this inventoryNumber exists');
        }
        return true;
    }

    /**
     * @param string $inventoryNumber
     * @return bool
     * @throws Exception
     */
    protected function validateInventoryNumber(string $inventoryNumber): bool
    {
        if (empty(trim($inventoryNumber))) {
            throw new Exception('Not a valid InventoryItem1C\'s inventoryNumber value');
        }
        return true;
    }

    /**
     * @param string $inventoryNumber
     * @return string
     */
    protected function sanitizeInventoryNumber(string $inventoryNumber): string
    {
        return trim($inventoryNumber);
    }

    /**
     * @param string $serialNumber
     * @return bool
     * @throws Exception
     */
    protected function validateSerialNumber(string $serialNumber): bool
    {
        if (empty(trim($serialNumber)) && self::EMPTY != $serialNumber) {
            throw new Exception('Not a valid InventoryItem1C\'s serialNumber value');
        }
        return true;
    }

    /**
     * @param string $serialNumber
     * @return string
     */
    protected function sanitizeSerialNumber(string $serialNumber): string
    {
        return trim($serialNumber);
    }
}
