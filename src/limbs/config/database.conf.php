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
        ],
        [
            'sqlsrv' => [
                'host' => 'localhost',
                'dbname' => 'test',
                'user' => 'root',
                'password' => 'root',
                'charset' => 'utf8',
                'port' => '3306',
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
            ],
        ],
    ],
];
