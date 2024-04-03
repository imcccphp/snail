<?php
namespace Imccc\Snail\Core;

use Imccc\Snail\Core\Container;

class Dispatcher
{
    protected $routes;
    protected $middlewares = [];
    protected $container;

    public function __construct(Container $container, $routes)
    {
        $this->routes = $routes;
        $this->container = $container;
    }

    public function addMiddleware(string $middlewareClass)
    {
        $middlewareInstance = $this->container->make($middlewareClass);
        $this->middlewares[] = $middlewareInstance;
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
        // 获取路由信息
        $parsedRoute = $this->routes;

        if (!empty($parsedRoute['is_static'])) {
            // 这是一个静态文件请求，直接返回文件内容
            header('Content-Type: ' . mime_content_type($parsedRoute['file_path']));
            readfile($parsedRoute['file_path']);
            exit;
        }

        // 如果路由是闭包，则直接执行闭包并退出
        if ($parsedRoute['is_closure']) {
            $closure = $parsedRoute['closure'];
            if (is_callable($closure)) {
                $closure(); // 执行闭包
                exit(); // 执行完闭包后退出
            }
        } elseif (in_array('404', $this->routes)) {
            // 如果路由不存在，则返回 404
            header('HTTP/1.1 404 Not Found');
            exit('404 Not Found');
        } else {
            // 如果路由不是闭包且不是 404，则继续执行中间件和路由处理器
            $this->executeMiddlewares(function () {
                $this->executeRouteHandler();
            });
            exit(); // 执行完路由处理器后退出
        }
    }

    protected function executeMiddlewares($finalHandler)
    {
        // 如果没有中间件，则直接执行最终处理器
        if (empty($this->middlewares)) {
            $finalHandler();
            return;
        }

        // 构建中间件执行链
        $next = $finalHandler;
        foreach ($this->middlewares as $middleware) {
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
        $controllerObj = new $controllerClass($this->container, $this->routes);

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
        // 设置 HTTP 状态码为 500
        header('HTTP/1.1 500 Internal Server Error');

        // 输出详细的错误信息
        echo 'Internal Server Error: ' . $exception->getMessage() . '<br>';
        echo 'File: ' . $exception->getFile() . '<br>';
        echo 'Line: ' . $exception->getLine() . '<br>';
        echo 'Trace: <pre>' . $exception->getTraceAsString() . '</pre>';

        // 退出脚本执行
        exit();
    }
}
