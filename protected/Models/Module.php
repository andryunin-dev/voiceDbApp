<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
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

    public function validateTitle($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое название модуля');
        }
        return true;

    }

    public function validate()
    {
        if (false === $this->isNew()) {
            return true;
        }
        if (false !== Module::findByColumn('title', $this->title)) {
            throw new Exception('Такой модуль уже существует');
        }
        return true;
    }
}