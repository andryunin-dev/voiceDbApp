<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Dbal\Query;
use T4\Dbal\QueryBuilder;
use T4\Orm\Model;

/**
 * Class Module
 * @package App\Models
 *
 * @property string $title
 * @property string $description
 *
 * @property Vendor $vendor
 * @property Collection|ModuleItem[] $moduleItems
 */
class Module extends Model
{
    const SQL = [
        'findBy_Vendor_Title' => '
            SELECT module.*
            FROM equipment.modules module
              JOIN equipment.vendors vendor ON vendor.__id = module.__vendor_id
            WHERE vendor.__id = :vendor_id AND module.title = :title',
    ];

    protected static $schema = [
        'table' => 'equipment.modules',
        'columns' => [
            'title' => ['type' => 'string'],
            'description' => ['type' => 'string']
        ],
        'relations' => [
            'vendor' => ['type' => self::BELONGS_TO, 'model' => Vendor::class],
            'moduleItems' => ['type' => self::HAS_MANY, 'model' => ModuleItem::class]
        ]
    ];

    protected function validateTitle($val)
    {
//        if (empty(trim($val))) {
//            throw new Exception('Пустое название модуля');
//        }

        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function validate()
    {
        if (! ($this->vendor instanceof Vendor)) {
            throw new Exception('Module: Неверный тип Vendor');
        }

        $module = Module::findByVendorTitle($this->vendor, $this->title);

        if (true === $this->isNew && ($module instanceof Module)) {
            throw new Exception('Такой Module уже существует-> ' . $this->title);
        }

        if (true === $this->isUpdated && ($module instanceof Module) && ($module->getPk() != $this->getPk())) {
            throw new Exception('Такой Module уже существует-> ' . $this->title);
        }

        return true;
    }

    /**
     * @param Vendor $vendor
     * @param $title
     * @return mixed
     */
    public static function findByVendorTitle(Vendor $vendor, $title) {
        $query = new Query(self::SQL['findBy_Vendor_Title']);
        return self::findByQuery($query, [':vendor_id' => $vendor->getPk(), ':title' => $title]);
    }

    public static function getEmpty()
    {
        return self::findByColumn('type','');
    }

    /**
     * @param Vendor $vendor
     * @param string $title
     * @return Module
     * @throws MultiException
     */
    public static function getInstanceByVendorTitle(Vendor $vendor, string $title): Module
    {
        $module = self::findByVendorTitle($vendor, $title);
        if (false === $module) {
            $module = (new self())->fill(['vendor' => $vendor, 'title' => $title ])->save();
        }
        return $module;
    }
}
