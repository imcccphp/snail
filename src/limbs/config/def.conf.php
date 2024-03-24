<?php
return [
    'route' => [
        'namespace' => 'app\controller',
        'controller' => 'Index',
        'action' => 'index',
    ],
    'group' => [
        'web' => [
            'namespace' => 'web\controller',
            'controller' => 'Index',
            'action' => 'index',
        ],
        'api' => [
            'namespace' => 'api\controller',
            'controller' => 'Index',
            'action' => 'index',
        ],
        'admin' => [
            'namespace' => 'admin\controller',
            'controller' => 'Index',
            'action' => 'index',
        ],
    ],

];
