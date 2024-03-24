<?php
/**
 * 路由类
 * @describe 获取请求的URL并匹配到对应的控制器和方法 返回一个数组给调度类处理，
 * 支持RESTful路由、支持路由表、支持路由参数、响应url和pathinfo、混杂模式、支持路由别名、
 * 支持路由分组、支持路由中间件、支持路由参数、
 * @package Snail
 * @author Imccc
 * @version 0.0.1
 * @copyright Copyright (c) 2024 Imccc
 * @license MIT
 * @link https://github.com/Imcccphp/Snail
 */

namespace Imccc\Snail\Core;

use Imccc\Snail\Core\Config;

class Router
{
    private $routes = []; // 路由表
    private $parsedRoute = []; // 解析后的路由信息
    private $def = [];

    public function __construct($routes = null)
    {
        $this->loadRoutes($routes);
        $this->match();
    }

    /**
     * 加载路由表
     * @param array $routes
     */
    private function loadRoutes($routes = null)
    {
        if ($routes !== null) {
            $this->routes = $routes;
        } else {
            $this->routes = Config::get('route'); // 从配置文件加载路由表
        }
        $this->def = Config::get('def');
    }

    /**
     * 匹配路由
     */
    private function match()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']); // 获取请求方法
        $uri = isset($_SERVER['REQUEST_URI']) ? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') : ''; // 获取请求的URI路径
        if ($uri === '') {
            $uri = '/'; // 如果URI为空，则设置为根路径
        }

        foreach ($this->routes as $route => $routegroup) {
            foreach ($routegroup as $route => $handler) {
                $handler = $this->parseHandler($handler); // 解析路由处理程序
                $routeMethods = isset($handler['method']) ? explode('|', strtoupper($handler['method'])) : []; // 获取路由允许的请求方法
                if (!empty($routeMethods) && !in_array($method, $routeMethods)) {
                    continue; // 如果请求方法不在允许的方法列表中，则继续下一次循环
                }
                $pattern = $this->buildPattern($route); // 构建路由匹配正则表达式
                if (preg_match($pattern, $uri, $matches)) { // 使用正则表达式匹配URI
                    array_shift($matches);
                    $params = $matches; // 获取匹配的参数
                    if ($handler['is_closure']) { // 如果是闭包函数
                        $this->parsedRoute = [
                            'is_closure' => true,
                            'closure' => $handler['closure'](...$params), // 执行闭包函数并传入参数
                        ];
                        return;
                    } else {
                        $this->parsedRoute = [
                            'is_closure' => false,
                            'namespace' => $handler['namespace'] ?? '',
                            'controller' => $handler['controller'] ?? '',
                            'action' => $handler['action'] ?? '',
                            'params' => $params,
                            'method' => $method,
                            'middlewares' => $handler['middlewares'] ?? [], // 中间件
                        ];
                        return;
                    }
                }
            }
        }

        // 如果没有匹配到路由，则尝试直接解析
        if (empty($this->parsedRoute)) {
            $this->parseDirectly($uri);
        } else {
            $this->parsedRoute = ['is_closure' => false, 'status' => 404]; // 如果没有匹配到路由，则返回404状态
        }
    }

    /**
     * 解析路由处理程序 无路由表
     * @param $handler
     * @return array
     */
    private function parseDirectly($uri)
    {
        // 移除URL后缀（如果有的话）
        $uri = $this->removeUrlSuffix($uri);

        // 使用'/'分割URI
        $segments = explode('/', $uri);

        // 约定前三个段为group, controller和action
        $group = isset($segments[0]) && $segments[0] ? ucfirst($segments[0]) : 'DefaultGroup';
        $controller = isset($segments[1]) && $segments[1] ? ucfirst($segments[1]) : 'Home';
        $action = $segments[2] ?? 'index';

        // 解析键值对参数
        $params = [];
        for ($i = 3; $i < count($segments); $i += 2) {
            if (isset($segments[$i + 1])) {
                $params[$segments[$i]] = $segments[$i + 1];
            }
        }

        // 填充解析后的路由信息
        $this->parsedRoute = [
            'is_closure' => false,
            'group' => $group,
            'controller' => $controller,
            'action' => $action,
            'params' => $params,
            'method' => $_SERVER['REQUEST_METHOD'],
            // 根据需要添加中间件和其他信息
        ];
    }

    /**
     * 解析路由处理程序
     * @param array $handler 路由处理程序
     * @return array 解析后的路由处理程序
     */
    private function parseHandler($handler)
    {
        if (is_callable($handler[0])) {
            return ['is_closure' => true, 'closure' => $handler[0]]; // 如果是闭包函数，则返回闭包函数
        } else {
            list($method, $class) = explode('@', $handler[2]);
            $namespace = $handler[1];
            $middlewares = $handler['middlewares'] ?? []; // 中间件
            return [
                'is_closure' => false,
                'namespace' => $namespace,
                'controller' => $class,
                'action' => $method,
                'path' => $handler[0] ?? '', // 路径
                'method' => $handler[3] ?? 'GET',
                'params' => $handler[4] ?? [], // 参数
                'middlewares' => $middlewares,
            ];
        }
    }

    /**
     * 构建路由正则表达式
     * @param $route
     * @return string
     */
    private function buildPattern($route)
    {
        return '#^' . preg_replace_callback('/:(\w+)/', function ($matches) { // 构建路由正则表达式
            return isset($this->patterns[$matches[1]]) ? '(' . $this->patterns[$matches[1]] . ')' : '([^/]+)';
        }, $route) . '$#';
    }

    /**
     * 移除URL后缀
     * @param $uri
     * @return string
     */
    private function removeUrlSuffix($uri)
    {
        foreach ($this->supportedSuffixes as $suffix) {
            if (substr($uri, -strlen($suffix)) === $suffix) {
                $uri = substr($uri, 0, -strlen($suffix));
                break;
            }
        }
        return $uri;
    }

    public function getRouteInfo()
    {
        return $this->parsedRoute;
    }
}
