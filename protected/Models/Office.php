<?php

namespace App\Models;

use T4\Orm\Model;

/**
 * Class Office
 * @package App\Models
 *
 * @property string $title Office title
 * @property string $details Any additional info about office in JSONB format
 * @property string $comment
 * @property Address $address
 * @property OfficeStatus $status
 */
class Office extends Model
{
    protected static $schema = [
        'table' => 'company.offices',
        'columns' => [
            'title' => ['type' => 'string'],
            'details' => ['type' => 'jsonb'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'address' => ['type' => self::BELONGS_TO, 'model' => Address::class],
            'status' => ['type' => self::BELONGS_TO, 'model' => OfficeStatus::class],
        ]
    ];

}