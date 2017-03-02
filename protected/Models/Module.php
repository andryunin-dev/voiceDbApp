<?php

namespace App\Models;

use T4\Core\Collection;
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

    public function validateTitle($title)
    {
        return (!empty(trim($title)));
    }

    public function validate()
    {
        if (
            true === empty(trim($this->title)) ||
            false === $this->vendor
        ) {
            return false;
        }
        return true;
    }

}