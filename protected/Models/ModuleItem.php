<?php

namespace App\Models;

use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class ModuleItem
 * @package App\Models
 *
 * @property string $serialNumber
 * @property string $inventoryNumber
 * @property string $details
 * @property string $comment
 * @property string $lastUpdate
 *
 * @property Module $module
 * @property Appliance $appliance
 * @property Office $location
 */
class ModuleItem extends Model
{
    protected static $schema = [
        'table' => 'equipment.moduleItems',
        'columns' => [
            'serialNumber' => ['type' => 'string'],
            'inventoryNumber' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'string'],
            'lastUpdate' => ['type' => 'datetime']
        ],
        'relations' => [
            'module' => ['type' => self::BELONGS_TO, 'model' => Module::class],
            'appliance' => ['type' => self::BELONGS_TO, 'model' => Appliance::class],
            'location' => ['type' => self::BELONGS_TO, 'model' => Office::class, 'by' => '__location_id']
        ]
    ];

    protected function validateSerialNumber($val)
    {
        if (empty(trim($val))) {
            throw new Exception('ModuleItem: Empty serial number');
        }

        return trim($val);
    }

    protected function sanitizeSerialNumber($val)
    {
        return trim($val);
    }

    protected function validate()
    {
        if (! ($this->module instanceof Module)) {
            throw new Exception('ModuleItem: Неверный тип Module');
        }
        if (! ($this->appliance instanceof Appliance || null === $this->appliance)) {
            throw new Exception('ModuleItem: Неверный тип Appliance');
        }
        if (! ($this->location instanceof Office)) {
            throw new Exception('ModuleItem: Неверный тип Office');
        }

        $moduleItem = ModuleItem::findByModuleSerial($this->module, $this->serialNumber);

        if (true === $this->isNew && ($moduleItem instanceof ModuleItem)) {
            throw new Exception('Такой ModuleItem уже существует');
        }

        if (true === $this->isUpdated && ($moduleItem instanceof ModuleItem) && ($moduleItem->getPk() != $this->getPk())) {
            throw new Exception('Такой ModuleItem уже существует');
        }

        return true;
    }

    protected function beforeSave()
    {
        if ($this->appliance instanceof Appliance) {
            $this->location = $this->appliance->location;
            $this->lastUpdate = (new \DateTime())->format('Y-m-d H:i:sP');
        }

        return parent::beforeSave();
    }

    public function unlinkAppliance()
    {
        $this->appliance = null;
    }

    /**
     * @param Module $module
     * @param $serialNumber
     * @return ModuleItem|bool
     */
    public static function findByModuleSerial(Module $module, $serialNumber)
    {
        return $module->moduleItems->filter(
            function($moduleItem) use ($serialNumber) {
                return $serialNumber == $moduleItem->serialNumber;
            }
        )->first();
    }
}
