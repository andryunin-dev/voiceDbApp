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

    const NO_NAME = 'NO_NAME';

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
        if (false === $this->isNew()) {
            return true;
        }
        if (false !== Vendor::findByColumn('title', $this->title)) {
            throw new Exception('Такой производитель уже существует');
        }
        return true;
    }

    public static function getByTitle(string $title)
    {
        if (empty($title)) {
            $title = self::NO_NAME;
        }

        $vendor = self::findByTitle($title);

        if (false == $vendor) {
            try {
                self::getDbConnection()->beginTransaction();
                (new self())
                    ->fill([
                        'title' => $title
                    ])
                    ->save();
                self::getDbConnection()->commitTransaction();
            } catch (MultiException $e) {
                self::getDbConnection()->rollbackTransaction();
            } catch (Exception $e) {
                self::getDbConnection()->rollbackTransaction();
            }

            return self::findByTitle($title);
        }

        return $vendor;
    }
}
