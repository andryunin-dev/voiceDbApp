<?php

namespace App\Models;

use T4\Orm\Model;

/**
 * Class Contact
 * @package App\Models
 *
 * @property string $contact
 * @property string $extension
 * @property string $details
 * @property string $comment
 *
 * @property Person $person
 * @property ContactType $type
 */
class Contact extends Model
{
    protected static $schema = [
        'table' => 'contact_book.contacts',
        'columns' => [
            'contact' => ['type' => 'string'],
            'extension' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'person' => ['type' => self::BELONGS_TO, 'model' => Person::class],
            'type' => ['type' => self::BELONGS_TO, 'model' => ContactType::class, 'by' => '__contact_type_id']
        ]
    ];
}