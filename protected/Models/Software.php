<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class Software
 * @package App\Models
 *
 * @property string $title
 *
 * @property Vendor $vendor
 * @property Collection|SoftwareItem[] $softwareItems
 */
class Software extends Model
{
    const NO_NAME = 'NO_NAME';

    protected static $schema = [
        'table' => 'equipment.software',
        'columns' => [
            'title' => ['type' => 'string']
        ],
        'relations' => [
            'vendor' => ['type' => self::BELONGS_TO, 'model' => Vendor::class],
            'softwareItems' => ['type' => self::HAS_MANY, 'model' => SoftwareItem::class, 'by' => '__software_id']
        ]
    ];

    public function validateTitle($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое название ПО');
        }
        return true;

    }

    public function validate()
    {
        if (false === $this->isNew()) {
            return true;
        }

        if ((false !== $existed = self::findByColumn('title', $this->title)) &&
            $this->vendor->getPk() == $existed->vendor->getPk()
        ) {
            throw new Exception('Такое ПО уже существует');
        }

        return true;
    }

    public static function getByVendor(Vendor $vendor, string $applianceSoft)
    {
        if (empty($applianceSoft)) {
            $applianceSoft = self::NO_NAME;
        }

        $software = self::findByVendorPlatform($vendor, $applianceSoft);

        if (false == $software) {
            try {
                self::getDbConnection()->beginTransaction();
                (new self())
                    ->fill([
                        'title' => $applianceSoft,
                        'vendor' => $vendor
                    ])
                    ->save();
                self::getDbConnection()->commitTransaction();
            } catch (MultiException $e) {
                self::getDbConnection()->rollbackTransaction();
            } catch (Exception $e) {
                self::getDbConnection()->rollbackTransaction();
            }

            return self::findByVendorPlatform($vendor, $applianceSoft);
        }

        return $software;
    }

    public static function findByVendorPlatform(Vendor $vendor, string $applianceSoft)
    {
        $query = (new Query())
            ->select()
            ->from(self::getTableName())
            ->where('title = :title AND __vendor_id = :__vendor_id')
            ->params([':title' => $applianceSoft, ':__vendor_id' => $vendor->getPk()]);

        return self::findByQuery($query);
    }
}