<?php

namespace App\Models;

use T4\Core\Collection;
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