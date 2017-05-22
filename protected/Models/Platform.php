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
    public static function findByVendorTitle(Vendor $vendor, $title) {
        return $vendor->platforms->filter(
            function ($platform) use ($title) {
                return $title == $platform->title;
            }
        )->first();
    }
}
