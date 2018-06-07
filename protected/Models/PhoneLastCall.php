<?php

namespace App\Models;

use T4\Orm\Model;

class PhoneLastCall extends Model
{
    protected static $schema = [
        'db' => 'cdr',
        'table' => 'cdr_call_activ.dev_nd',
        'columns' => [
            'dev' => ['type' => 'string'],
            'date' => ['type' => 'date']
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }
}
