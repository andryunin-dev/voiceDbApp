<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class ContractType
 * @package App\Models
 *
 * @property string $name
 */
class ContractType extends Model
{
    protected static $schema = [
        'table' => 'partners.contractTypes',
        'columns' => [
            'title' => ['type' => 'string']
        ],
        'relations' => [
            'contracts' => ['type' => self::HAS_MANY, 'model' => Contract::class, 'by' => '__contract_type_id']
        ]
    ];
}