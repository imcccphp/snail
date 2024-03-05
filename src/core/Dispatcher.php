<?php
/**
 * 调度类
 *
 * @package Imccc\Snail
 * @since 0.0.1
 * @author Imccc
 * @copyright Copyright (c) 2024 Imccc.
 */
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
        if (in_array('404', $this->routes)) {
            header('HTTP/1.1 404 Not Found');
            exit('404 Not Found');
        } else {
            $namespace = $this->routes['namespace'];
            $controller = $this->routes['controller'];
            $action = $this->routes['action'];
            $params = $this->routes['params'];
            $method = $this->routes['method'];
            $postData = $this->routes['input'];
            $file = $this->routes['file'];
        }
        // 构建控制器类名
        $controllerClass = $namespace . '\\' . $controller;

        // 检查控制器类是否存在
        if (!class_exists($controllerClass)) {
            header('HTTP/1.1 500 Internal Server Error');
            exit('Controller class not found');
        }

        // 创建控制器对象，并传入路由参数数组
        $controllerObj = new $controllerClass($this->routes);

        // 检查控制器方法是否存在
        if (!method_exists($controllerObj, $action)) {
            header('HTTP/1.1 500 Internal Server Error');
            exit('Action method not found');
        }

        // 调用控制器方法
        $result = call_user_func([$controllerObj, $action]);

        // 如果方法返回一个响应，直接输出
        if (!empty($result)) {
            echo $result;
        }

        return $this; // 支持链式调用
    }

}
