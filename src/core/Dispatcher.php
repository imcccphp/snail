<?php
namespace Imccc\Snail\Core;

class Dispatcher
{
    protected $routes;
    protected $middlewares = [];

    public function __construct($routes)
    {
        $this->routes = $routes;
    }

    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
        return $this; // 支持链式调用
    }

    public function dispatch()
    {
        try {
            $this->handleRequest();
        } catch (\Exception $e) {
            // 处理异常
            $this->handleError($e);
        }
    }

    protected function handleRequest()
    {
        // 如查是闭包，则执行闭包函数和参数
        if ($this->routes['is_closure']) {
            $closure = $this->routes['closure'];
            exit();
        } else if (in_array('404', $this->routes)) {
            header('HTTP/1.1 404 Not Found');
            exit('404 Not Found');
        } else {
            $this->executeMiddlewares(function () {
                $this->executeRouteHandler();
            });
        }
    }

    protected function executeMiddlewares($finalHandler)
    {
        // 构建中间件执行链
        $next = $finalHandler;
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = function () use ($middleware, $next) {
                return $middleware->handle($next);
            };
        }

        // 执行中间件链
        $next();
    }

    protected function executeRouteHandler()
    {
        $namespace = $this->routes['namespace'];
        $controller = $this->routes['controller'];
        $action = $this->routes['action'];

        // 构建控制器类名
        $controllerClass = $namespace . '\\' . $controller;

        // 检查控制器类是否存在
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException('Controller class not found');
        }

        // 创建控制器对象，并传入路由参数数组
        $controllerObj = new $controllerClass($this->routes);

        // 检查控制器方法是否存在
        if (!method_exists($controllerObj, $action)) {
            throw new \RuntimeException('Action method not found');
        }

        // 调用控制器方法
        $result = call_user_func([$controllerObj, $action]);

        // 输出结果
        if (!empty($result)) {
            echo $result;
        }
    }

    protected function handleError($exception)
    {
        header('HTTP/1.1 500 Internal Server Error');
        exit('Internal Server Error: ' . $exception->getMessage());
    }
}
