<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
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
        if (false === $this->isNew()) {
            return true;
        }
        if (false !== Platform::findByColumn('title', $this->title)) {
            throw new Exception('Такая платформа уже существует');
        }
        return true;
    }
}