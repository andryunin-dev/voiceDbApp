<?php

namespace App\Models;

use T4\Orm\Model;

/**
 * Class Office
 * @package App\Models
 *
 * @property string $title Office title
 * @property int $lotusId
 * @property Address $address
 * @property OfficeStatus $status
 * @property string $details Any additional info about office in JSONB format
 * @property string $comment
 */
class Office extends Model
{
    protected static $schema = [
        'table' => 'company.offices',
        'columns' => [
            'title' => ['type' => 'string'],
            'lotusId' => ['type' => 'string'],
            'details' => ['type' => 'jsonb'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'address' => ['type' => self::BELONGS_TO, 'model' => Address::class],
            'status' => ['type' => self::BELONGS_TO, 'model' => OfficeStatus::class, 'on' => '__office_status_id']
        ]
    ];

}