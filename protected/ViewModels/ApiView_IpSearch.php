<?php

namespace App\ViewModels;

use T4\Orm\Model;

class ApiView_IpSearch extends Model
{
    const PK = 'id';
    protected static $schema = [
        'table' => 'api_view.ip_search',
        'columns' => [
            'id' => ['type' => 'int', 'length' => 'big'],
            'ip' => ['type' => 'text'],
            'rec_type' => ['type' => 'text'],
        ]
    ];
    protected function beforeSave()
    {
        return false;
    }
}