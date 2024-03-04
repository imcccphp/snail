<?php

namespace Imccc\Snail\Core;

class Dispatcher
{
    protected $routes;

    public function __construct($routes)
    {
        $this->routes = $routes;

    }

    public function dispatch()
    {
        if (count($this->routes) < 1) {
            header('HTTP/1.1 404 Not Found');
            exit('404 Not Found');
        } else {
            $namespace = $this->routes['namespace'];
            $controller = $this->routes['controller'];
            $action = $this->routes['action'];
            $params = $this->routes['params'];
            $method = $this->routes['method'];
            $postData = $this->routes['post_data'] ?? [];
        }
        // 构建控制器类名
        $controllerClass = $namespace . '\\' . $controller;

        // 检查控制器类是否存在
        if (!class_exists($controllerClass)) {
            header('HTTP/1.1 500 Internal Server Error');
            exit('Controller class not found');
        }

        // 创建控制器对象
        $controllerObj = new $controllerClass();

        // 检查控制器方法是否存在
        if (!method_exists($controllerObj, $action)) {
            header('HTTP/1.1 500 Internal Server Error');
            exit('Action method not found');
        }

        // 调用控制器方法
        $result = call_user_func_array([$controllerObj, $action], $params);

        // 如果方法返回一个响应，直接输出
        if (!empty($result)) {
            echo $result;
        }

        // 如果是 POST 请求，处理 POST 数据
        if ($method === 'POST') {
            // 处理 POST 数据
            self::processPostData($postData);
        }
    }

    private static function processPostData($postData)
    {
        // 处理 POST 数据的逻辑
        // 这里可以根据实际需求进行处理，例如存储到数据库或者执行其他操作
    }
}
