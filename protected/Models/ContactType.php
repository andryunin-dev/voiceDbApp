<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class ContactType
 * @package App\Models
 *
 * @property string $type
 *
 * @property Collection|Contact[] $contacts
 */
class ContactType extends Model
{
    protected static $schema = [
        'table' => 'contact_book.contactTypes',
        'columns' => [
            'type' => ['type' => 'string']
        ],
        'relations' => [
            'contacts' => ['type' => self::HAS_MANY, 'model' => Contact::class, 'by' => '__contact_type_id']
        ]
    ];
}