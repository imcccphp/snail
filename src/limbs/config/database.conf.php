<?php

return [
    'db' => 'mysql',
    'dsn' => [
        'mysql' => [
            'host' => 'localhost',
            'dbname' => 'test',
            'user' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
            'port' => '3306',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
    [
        'sqlsrv' => [
            'host' => 'localhost',
            'dbname' => 'test',
            'user' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
            'port' => '3306',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
    [
        'oci' => [
            'host' => 'localhost',
            'dbname' => 'test',
            'user' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
            'port' => '3306',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
];
