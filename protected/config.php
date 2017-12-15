<?php

return [
    'db' => [
        'default' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'user' => 'postgres',
            'password' => '',
            'dbname' => 'phpVDB'
        ],
        'phpUnitTest' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'user' => 'postgres',
            'password' => '',
            'dbname' => 'phpUnitTest'
        ],
        'lotusData' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'user' => 'postgres',
            'password' => '',
            'dbname' => 'LotusData'
        ]
    ],
    'axl' => [
        'username' => '',
        'password' => '',
    ],
    'ssh' => [
        'login' => '',
        'password' => '',
    ]
];
