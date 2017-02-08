<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class Organisation
 * @package App\Models
 *
 * @property string $title
 * @property Collection|City[] $offices
 */
class Organisation extends Model
{
    protected static $schema = [
        'table' => 'partners.organisations',
        'columns' => [
            'title' => ['type' => 'string']
        ],
        'relations' => [
            'offices' => ['type' => self::HAS_MANY, 'model' => PartnerOffice::class]
        ]
    ];
}