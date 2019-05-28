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
    const SQL = [
        'findBy_Vendor_Title' => '
            SELECT software.*
            FROM equipment.software software
              JOIN equipment.vendors vendor ON vendor.__id = software.__vendor_id
            WHERE vendor.__id = :vendor_id AND software.title = :title',
    ];

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
        return true;
    }

    public function validate()
    {
        if (! ($this->vendor instanceof Vendor)) {
            throw new Exception('Software: Неверный тип Vendor');
        }

        $software = Software::findByVendorTitle($this->vendor, $this->title);

        if (true === $this->isNew && ($software instanceof Software)) {
            throw new Exception('Такое ПО уже существует');
        }

        if (true === $this->isUpdated && ($software instanceof Software) && ($software->getPk() != $this->getPk())) {
            throw new Exception('Такое ПО уже существует');
        }

        return true;
    }

    public static function getEmpty()
    {
        return self::findByColumn('title', '');
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

    /**
     * @param Vendor $vendor
     * @param string $title
     * @return Software
     * @throws MultiException
     */
    public static function getInstanceByVendorTitle(Vendor $vendor, string $title): Software
    {
        $software = self::findByVendorTitle($vendor, $title);
        if (false === $software) {
            $software = (new self())->fill(['vendor' => $vendor, 'title' => $title])->save();
        }
        return $software;
    }
}
