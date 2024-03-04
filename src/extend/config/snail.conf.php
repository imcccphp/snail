<?php

return [
    'about' => [
        'name' => 'Snail',
        'version' => '1.0.0',
        'author' => 'sam',
        'email' => 'sam@imccc.cc',
        'copyright' => 'Copyright (c) 2024 Imccc',
        'license' => 'Apache License 2.0',
        'description' => 'Snail is a simple PHP framework for building web applications.',
    ],
    'config' => [
        'debug' => true,
        'error_reporting' => E_ALL,
        'timezone' => 'Asia/Shanghai',
        'charset' => 'utf-8',
        'runtime' => 'runtime', // runtime 目录
    ],
    'driver' => [
        'session' => 'file',
        'cache' => 'file',
        'log' => 'file',
    ],

];
