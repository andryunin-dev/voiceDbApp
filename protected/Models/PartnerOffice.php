<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class PartnerOffice
 * @package App\Models
 *
 * @property string $details
 * @property string $comment
 * @property Collection|City[] $offices
 */
class PartnerOffice extends Model
{
    protected static $schema = [
        'table' => 'partners.offices',
        'columns' => [
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'organisation' => ['type' => self::BELONGS_TO, 'model' => Organisation::class],
            'address' => ['type' => self::BELONGS_TO, 'model' => Address::class],
            'persons' => ['type' => self::HAS_MANY, 'model' => Person::class, 'by' => '__workplace_id'],
            'contracts' => ['type' => self::HAS_MANY, 'model' => Contract::class, 'by' => '__partner_office_id']
        ]
    ];
}