<?php

return [
    'on' => [
        'def' => true,
        'log' => true,
        'info' => true,
        'error' => true,
        'warning' => true,
        'debug' => true,
        'sql' => true,
        'sqlerr' => true,
        'view' => true,
        'model' => true,
        'controller' => true,
        'container' => true,
        'socket' => true,
        'router' => true,
        'request' => true,
        'response' => true,
        'database' => true,
        'http' => true,
    ],
    'logprefix' => [
        'def' => '_DEF_',
        'log' => '_LOG_',
        'info' => '_INFO_',
        'error' => '_ERROR_',
        'warning' => '_WARNING_',
        'debug' => '_DEBUG_',
        'sql' => '_SQL_',
        'sqlerr' => '_SQL_ERROR_',
        'view' => '_VIEW_',
        'model' => '_MODEL_',
        'controller' => '_CONTROLLER_',
        'container' => '_CONTAINER_',
        'socket' => '_SOCKET_',
        'router' => '_ROUTER_',
        'request' => '_REQUEST_',
        'response' => '_RESPONSE_',
        'database' => '_DATABASE_',
        'http' => '_HTTP_',
    ],
    'log_file_path' => dirname($_SERVER['DOCUMENT_ROOT']) . '/runtime/logs', // 日志文件路径
    'log_type' => 'database', // 日志类型，可选值：file, server, database
    'batch_size' => 100, // 批量处理的大小,仅对file类型有效
];
