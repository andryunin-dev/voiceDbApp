<?php

namespace App\Models;

use T4\Core\Collection;
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

    public function validateTitle($title)
    {
        return (!empty(trim($title)));
    }

    public function validate()
    {
        if (
            true === empty(trim($this->title))
        ) {
            return false;
        }
        //только для нового объекта проверяем на наличие такого в БД
        if (true === $this->isNew() && false !== Vendor::findByColumn('title', trim($this->title))) {
            return false; //есть вендор с таким title
        }
        return true;
    }

}