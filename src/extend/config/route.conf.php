<?php
// 路由配置
// 'key' => ['请求地址或者闭包方式', '命名空间', '方法@类名', '模式'],
return [
    'user/:id/:action' => ['user/:id/:action', 'App\Controllers', 'user@Index', 'GET|POST'],

    // http://domain.com/hello/sam
    'hello/:any' => ['helle/:any', 'App\Controllers', 'hello@Index', 'get'],

    // http://domain.com
    '/' => ['index', 'App\Controllers', 'index@Index', 'get'], // 首页路由

    // http://domain.com/about
    'about' => ['about', 'App\Controllers', 'about@Index'],
    'snail' => ['snail', 'App\Controllers', 'snail@Index'],

    // http://domain.com/welcome 直接输出
    'welcome' => [function () {
        echo "Welcome to use Snail Framework";
    }, 'get|post'],

    // http://domain.com/submit
    'submit' => ['subbmit', 'App\Controllers', 'submit@Blog', 'post|get'],

];
