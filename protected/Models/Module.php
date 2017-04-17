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
 * @property string $partNumber
 * @property string $comment
 *
 * @property Vendor $vendor
 * @property Collection|ModuleItem[] $moduleItems
 */
class Module extends Model
{
    const NO_NAME = 'NO_NAME';
    const NO_DESCRIPTION = 'NO_DESCRIPTION';

    const MOTHERBOARD = 'motherboard'; // motherboard name in modules

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
        if (empty(trim($val))) {
            throw new Exception('Пустое название модуля');
        }
        return true;

    }

    protected function validate()
    {
        if (false === $this->isNew()) {
            return true;
        }

        if ((false !== $existed = self::findByColumn('title', $this->title)) &&
            $this->vendor->getPk() == $existed->vendor->getPk()
        ) {
            throw new Exception('Такой модуль уже существует');
        }

        return true;
    }


    public static function getByVendorAndTitle(Vendor $vendor, string $title, string $description)
    {
        if (empty($title)) {
            $title = self::NO_NAME;
        }

        if (empty($description)) {
            $description = self::NO_DESCRIPTION;
        }

        $module = self::findByVendorAndTitle($vendor, $title);

        if (false == $module) {
            try {
                self::getDbConnection()->beginTransaction();
                (new self())
                    ->fill([
                        'title' => $title,
                        'description' => $description,
                        'vendor' => $vendor
                    ])
                    ->save();
                self::getDbConnection()->commitTransaction();
            } catch (MultiException $e) {
                self::getDbConnection()->rollbackTransaction();
            } catch (Exception $e) {
                self::getDbConnection()->rollbackTransaction();
            }

            return self::findByVendorAndTitle($vendor, $title);
        }

        return $module;
    }

    public static function findByVendorAndTitle(Vendor $vendor, string $title)
    {
        $query = (new Query())
            ->select()
            ->from(self::getTableName())
            ->where('title = :title AND __vendor_id = :__vendor_id')
            ->params([':title' => $title, ':__vendor_id' => $vendor->getPk()]);

        return self::findByQuery($query);
    }
}
