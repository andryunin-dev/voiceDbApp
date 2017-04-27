<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Orm\Model;

/**
 * Class Vendor
 * @package App\Models
 *
 * @property string $title
 *
 * @property Collection|Appliance[] $appliances
 * @property Collection|Software[] $software
 * @property Collection|Platform[] $platforms
 * @property Collection|Module[] $modules
 */
class Vendor extends Model
{
    protected static $schema = [
        'table' => 'equipment.vendors',
        'columns' => [
            'title' => ['type' => 'string']
        ],
        'relations' => [
            'appliances' => ['type' => self::HAS_MANY, 'model' => Appliance::class],
            'software' => ['type' => self::HAS_MANY, 'model' => Software::class],
            'platforms' => ['type' => self::HAS_MANY, 'model' => Platform::class],
            'modules' => ['type' => self::HAS_MANY, 'model' => Module::class]
        ]
    ];

    public function validateTitle($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое название производителя');
        }

        return true;
    }

    public function validate()
    {
        $vendor = Vendor::findByColumn('title', $this->title);

        if (true === $this->isNew && ($vendor instanceof Vendor)) {
            throw new Exception('Такой производитель уже существует');
        }

        if (true === $this->isUpdated && ($vendor instanceof Vendor) && ($vendor->getPk() != $this->getPk())) {
            throw new Exception('Такой производитель уже существует');
        }

        return true;
    }
}
