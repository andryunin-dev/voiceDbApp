<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class Platform
 * @package App\Models
 *
 * @property string $title
 *
 * @property Vendor $vendor
 * @property Collection|PlatformItem[] $platformItems
 */
class Platform extends Model
{

    const NO_NAME = 'NO_NAME';

    protected static $schema = [
        'table' => 'equipment.platforms',
        'columns' => [
            'title' => ['type' => 'string']
        ],
        'relations' => [
            'vendor' => ['type' => self::BELONGS_TO, 'model' => Vendor::class],
            'platformItems' => ['type' => self::HAS_MANY, 'model' => PlatformItem::class]
        ]
    ];

    public function validateTitle($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое название платформы');
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
            throw new Exception('Такая платформа уже существует');
        }

        return true;
    }

    public static function getByVendor(Vendor $vendor, string $platformTitle)
    {
        if (empty($platformTitle)) {
            $platformTitle = self::NO_NAME;
        }

        $platform = self::findByVendorPlatform($vendor, $platformTitle);

        if (false == $platform) {
            try {
                self::getDbConnection()->beginTransaction();
                (new self())
                    ->fill([
                        'title' => $platformTitle,
                        'vendor' => $vendor
                    ])
                    ->save();
                self::getDbConnection()->commitTransaction();
            } catch (MultiException $e) {
                self::getDbConnection()->rollbackTransaction();
            } catch (Exception $e) {
                self::getDbConnection()->rollbackTransaction();
            }

            return self::findByVendorPlatform($vendor, $platformTitle);
        }

        return $platform;
    }

    public static function findByVendorPlatform(Vendor $vendor, string $platformTitle)
    {
        $query = (new Query())
            ->select()
            ->from(self::getTableName())
            ->where('title = :title AND __vendor_id = :__vendor_id')
            ->params([':title' => $platformTitle, ':__vendor_id' => $vendor->getPk()]);

        return self::findByQuery($query);
    }
}
