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
        if (! ($this->vendor instanceof Vendor)) {
            throw new Exception('Software: Неверный тип Vendor');
        }

        $title = $this->title;
        $this->vendor->refresh();

        $software = $this->vendor->software->filter(
            function ($software) use ($title) {
                return $title == $software->title;
            }
        )->first();

        if (true === $this->isNew && ($software instanceof Software)) {
            throw new Exception('Такое ПО уже существует');
        }

        if (true === $this->isUpdated && ($software instanceof Software) && ($software->getPk() != $this->getPk())) {
            throw new Exception('Такое ПО уже существует');
        }

        return true;
    }
}
