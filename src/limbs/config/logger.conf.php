<?php

return [
    'on' => [
        'sql' => true,
        'sqlerr' => true,
        'mvc' => true,
        'error' => true,
        'router' => true,
        'request' => true,
        'response' => true,
    ],
    'log_file_path' => dirname($_SERVER['DOCUMENT_ROOT']) . '/runtime/logs', // 日志文件路径
    'log_type' => 'file', // 日志类型，可选值：file, server, database
];
