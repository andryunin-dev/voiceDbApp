<?php

namespace App\Models;

use T4\Core\Collection;
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