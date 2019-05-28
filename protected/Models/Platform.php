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
    const SQL = [
        'findBy_Vendor_Title' => '
            SELECT platform.*
            FROM equipment.platforms platform
              JOIN equipment.vendors vendor ON vendor.__id = platform.__vendor_id
            WHERE vendor.__id = :vendor_id AND platform.title = :title',
    ];

    public function __construct($data = null)
    {
        $this->isHW = true;
        parent::__construct($data);
    }

    protected static $schema = [
        'table' => 'equipment.platforms',
        'columns' => [
            'title' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'isHW' => ['type' => 'boolean'],
        ],
        'relations' => [
            'vendor' => ['type' => self::BELONGS_TO, 'model' => Vendor::class],
            'platformItems' => ['type' => self::HAS_MANY, 'model' => PlatformItem::class]
        ]
    ];

    public function validateIsHW($val)
    {
        if (is_null($val)) {
            throw new Exception('The value of isHW is undefined');
        }
        if (!is_bool($val)) {
            throw new Exception('Invalid value of isHW ');
        }
        return true;
    }

    public function validateTitle($val)
    {
//        if (empty(trim($val))) {
//            throw new Exception('Пустое название платформы');
//        }

        return true;
    }

    public function validate()
    {
        if (! ($this->vendor instanceof Vendor)) {
            throw new Exception('Platform: Неверный тип Vendor');
        }

        $platform = Platform::findByVendorTitle($this->vendor, $this->title);

        if (true === $this->isNew && ($platform instanceof Platform)) {
            throw new Exception('Такая платформа уже существует');
        }

        if (true === $this->isUpdated && ($platform instanceof Platform) && ($platform->getPk() != $this->getPk())) {
            throw new Exception('Такая платформа уже существует');
        }

        return true;
    }

    /**
     * @param Vendor $vendor
     * @param $title
     * @return Platform|bool
     */
    public static function findByVendorTitle(Vendor $vendor, string $title) {
        $query = new Query(self::SQL['findBy_Vendor_Title']);
        return self::findByQuery($query, [':vendor_id' => $vendor->getPk(), ':title' => $title]);
    }

    public static function getEmpty()
    {
        return self::findByColumn('title', '');
    }

    /**
     * @param Vendor $vendor
     * @param string $title
     * @return Platform
     * @throws MultiException
     */
    public static function getInstanceByVendorTitle(Vendor $vendor, string $title): Platform
    {
        $platform = self::findByVendorTitle($vendor, $title);
        if (false === $platform) {
            $platform = (new self())->fill(['vendor' => $vendor, 'title' => $title])->save();
        }
        return $platform;
    }
}
