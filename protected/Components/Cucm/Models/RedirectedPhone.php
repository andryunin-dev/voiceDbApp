<?php
namespace App\Components\Cucm\Models;

use T4\Orm\Model;

class RedirectedPhone extends Model
{
    protected static $schema = [
        'table' => 'cucm.redirectedPhones', // todo - ???
        'columns' => [
            'device' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'css' => ['type' => 'string'],
            'devicepool' => ['type' => 'string'],
            'prefix' => ['type' => 'string'],
            'phonedn' => ['type' => 'string'],
            'alertingname' => ['type' => 'string'],
            'forwardall' => ['type' => 'string'],
            'forward_all_mail' => ['type' => 'string'],
            'forwardbusyinternal' => ['type' => 'string'],
            'forwardbusyexternal' => ['type' => 'string'],
            'forward_no_answer_internal' => ['type' => 'string'],
            'forward_no_answer_external' => ['type' => 'string'],
            'forward_unregistred_internal' => ['type' => 'string'],
            'forward_unregistred_external' => ['type' => 'string'],
            'cfnaduration' => ['type' => 'string'],
            'partition' => ['type' => 'string'],
            'model' => ['type' => 'string'],
            'cucm' => ['type' => 'string'],
            'lastUpdate' => ['type' => 'datetime'],
        ]
    ];
}
