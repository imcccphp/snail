<?php
// 路由配置
// 'key' => ['请求地址或者闭包方式', '命名空间', '方法@类名', '模式'],
return [
    'web' => [

        // http://domain.com
        '/' => ['index', 'app\controller', 'index@Index', 'get'], // 首页路由

        // http://domain.com/sendmail
        'sendmail' => ['sendmail', 'app\controller', 'sendmail@Index', 'post|get'],

        // http://domain.com/about
        'about' => ['about', 'app\controller', 'about@Index'],

        // http://domain.com/welcome 直接输出
        'welcome' => [function () {
            echo "Welcome to use Snail Framework";
        }, 'get|post'],

        // http://domain.com/submit
        'submit' => ['subbmit', 'app\controller', 'submit@Blog', 'post|get'],
    ],
    'api' => [
        'api/user/:id' => ['api/user/:id', 'app\controller', 'user@Index', 'GET|POST'],
        'api/user/:id/:action' => ['api/user/:id/:action', 'app\controller', 'user@Index', 'GET|POST'],
    ],
];
