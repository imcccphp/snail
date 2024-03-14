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
        if (isset($this->routes['is_closure']) && $this->routes['is_closure']) {
            $closure = $this->routes['closure'];
            $response = call_user_func($closure, ...($this->routes['params'] ?? [])); // 假设有参数
            echo $response;
            exit();
        } else if (isset($this->routes['status']) && $this->routes['status'] == '404') {
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
        $namespace = $this->routes['namespace'] ?? '';
        $controller = $this->routes['controller'] ?? '';
        $action = $this->routes['action'] ?? '';

        $controllerClass = $namespace . '\\' . $controller;
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller class {$controllerClass} not found");
        }

        $controllerObj = new $controllerClass($this->routes['params'] ?? []);

        if (!method_exists($controllerObj, $action)) {
            throw new \RuntimeException("Action method {$action} not found in {$controllerClass}");
        }

        $result = call_user_func([$controllerObj, $action]);
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
