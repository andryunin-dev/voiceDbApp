<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class Person
 * @package App\Models
 *
 * @property string $name
 */
class Person extends Model
{
    protected static $schema = [
        'table' => 'contact_book.persons',
        'columns' => [
            'name' => ['type' => 'string'],
            'position' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'office' => ['type' => self::BELONGS_TO, 'model' => PartnerOffice::class, 'by' => '__workplace_id'],
            'contacts' => ['type' => self::HAS_MANY, 'model' => Contact::class],
            'contracts' => [
                'type' => self::MANY_TO_MANY,
                'model' => Contract::class,
                'this' => '__person_id',
                'that' => '__contract_id'
            ]
        ]
    ];
}