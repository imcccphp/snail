<?php

return [
    'db' => 'mysql',
    'deleted_at' => 'deleted_at', // 软删除字段名称
    'soft_deletes' => true, // 是否启用软删除
    'dsn' => [
        'mysql' => [
            'host' => '127.0.0.1',
            'dbname' => 'snail_local',
            'user' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
            'port' => '3306',
            'prefix' => 'snail_',
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
            'prefix' => 'snail_',
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
            'prefix' => 'snail_',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
];
