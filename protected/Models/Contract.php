<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class Contract
 * @package App\Models
 *
 * @property string $number
 * @property string $date
 * @property string $pathToScan
 */
class Contract extends Model
{
    protected static $schema = [
        'table' => 'partners.contracts',
        'columns' => [
            'number' => ['type' => 'string'],
            'date' => ['type' => 'string'],
            'pathToScan' => ['type' => 'string']
        ],
        'relations' => [
            'contractType' => ['type' => self::BELONGS_TO, 'model' => ContactType::class],
            'persons' => [
                'type' => self::MANY_TO_MANY,
                'model' => Person::class,
                'this' => '__contract_id',
                'that' => '__person_id'
            ]
        ]
    ];
}