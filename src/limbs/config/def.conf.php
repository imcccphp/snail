<?php
return [
    //是否使用key2value键对模式
    'keyvalue' => true,
    //缺省设置
    'route' => [
        'namespace' => 'app\controller',
        'controller' => 'Index',
        'action' => 'index',
    ],
    //分组设置
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
