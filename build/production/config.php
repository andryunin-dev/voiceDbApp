<?php

return [
    'db' => [
        'default' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'user' => 'postgres',
            'password' => '{{db.password}}',
            'dbname' => '{{db.productionDb}}'
        ],
        'phpUnitTest' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'user' => 'postgres',
            'password' => '{{db.password}}',
            'dbname' => '{{db.unitTestDb}}'
        ],
        'lotusData' => [
            'driver' => 'pgsql',
            'host' => 'netcmdb.rs.ru',
            'user' => 'lotus',
            'password' => '{{db.lotusDB.password}}',
            'dbname' => '{{db.lotusDB.DbName}}'
        ]
    ],
    'axl' => [
        'username' => '{{axl.username}}',
        'password' => '{{axl.password}}',
    ],
    'ssh' => [
        'login' => '{{ssh.login}}',
        'password' => '{{ssh.password}}',
    ]
];