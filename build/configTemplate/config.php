<?php

return [
    'db' => [
        'default' => [
            'driver' => 'pgsql',
            'host' => '@db.host@',
            'user' => '@db.user@',
            'password' => '@db.password@',
            'dbname' => '@db.name@'
        ],
        'phpUnitTest' => [
            'driver' => 'pgsql',
            'host' => '@dbUnitTest.host@',
            'user' => '@dbUnitTest.user@',
            'password' => '@dbUnitTest.password@',
            'dbname' => '@dbUnitTest.name@'
        ],
        'lotusData' => [
            'driver' => 'pgsql',
            'host' => '@lotusDb.host@',
            'user' => '@lotusDb.user@',
            'password' => '@lotusDb.password@',
            'dbname' => '@lotusDb.name@'
        ],
        'cdr' => [
            'driver' => 'pgsql',
            'host' => '@cdrDb.host@',
            'user' => '@cdrDb.user@',
            'password' => '@cdrDb.password@',
            'dbname' => '@cdrDb.name@'
        ],
        'pcData' => [
            'driver' => 'pgsql',
            'host' => '@pcData.host@',
            'user' => '@pcData.user@',
            'password' => '@pcData.password@',
            'dbname' => '@pcData.name@'
        ]
    ],
    'axl' => [
        'username' => '@axl.username@',
        'password' => '@axl.password@',
    ],
    'snmp' => [
        'community' => '@snmp.community@',
    ],
    'ssh' => [
        'login' => '@ssh.login@',
        'password' => '@ssh.password@',
    ],
    'appParams' => [
        'maxAge' => 73,
    ]
];
