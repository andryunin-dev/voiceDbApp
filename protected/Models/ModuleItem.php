<?php

namespace App\Models;

use App\Storage1CModels\Module1C;
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
 * @property boolean $inUse if module using for any tasks
 * @property boolean $notFound if not found in last update
 *
 * @property Module $module
 * @property Appliance $appliance
 * @property Office $location
 * @property Module1C $module1C
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
            'lastUpdate' => ['type' => 'datetime'],
            'inUse' => ['type' => 'boolean'],
            'notFound' => ['type' => 'boolean']
        ],
        'relations' => [
            'module' => ['type' => self::BELONGS_TO, 'model' => Module::class],
            'appliance' => ['type' => self::BELONGS_TO, 'model' => Appliance::class],
            'location' => ['type' => self::BELONGS_TO, 'model' => Office::class, 'by' => '__location_id'],
            'module1C' => ['type' => self::HAS_ONE, 'model' => Module1C::class, 'by' => '__voice_module_id'],
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

        $moduleItem = ModuleItem::findByVendorSerial($this->module->vendor, $this->serialNumber);

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
        if (true === $this->isNew && null === $this->inUse) {
            $this->inUse();
        }
        if (!isset($this->notFound)) {
            $this->found();
        }

        if (false === $this->notFound) {
            $this->location = $this->appliance->location;
        }

        return parent::beforeSave();
    }

    public function unlinkAppliance()
    {
        $this->appliance = null;
        $this->notFound = true;
    }

    public function found()
    {
        $this->notFound = false;
    }

    public function notFound()
    {
        $this->notFound = true;
    }

    public function inUse()
    {
        $this->inUse = true;
    }

    public function notUse()
    {
        $this->inUse = false;
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

    /**
     * @param Vendor $vendor
     * @param $serialNumber
     * @return self|bool
     */
    public static function findByVendorSerial(Vendor $vendor, $serialNumber)
    {
        $moduleItems = self::findAllByColumn('serialNumber', $serialNumber);
        $moduleItem = $moduleItems->filter(
            function($moduleItem) use ($vendor) {
                return $moduleItem->module->vendor->title == $vendor->title;
            }
        )->first();
        if (is_null($moduleItem)) {
            return false;
        } else {
            return $moduleItem;
        }
    }

    public function lastUpdateDate()
    {
        return $this->lastUpdate ? (new \DateTime($this->lastUpdate))->format('Y-m-d') : null;
    }

    public function lastUpdateDateTime()
    {
        return $this->lastUpdate ? ('last update: ' . ((new \DateTime($this->lastUpdate))->setTimezone(new \DateTimeZone('Europe/Moscow')))->format('d.m.Y H:i \M\S\K(P)')) : null;
    }


    public function delete()
    {
        if (!is_null($this->module1C)) {
            $this->module1C->voiceModule = null;
            $this->module1C->save();
        }
        return parent::delete();
    }
}
