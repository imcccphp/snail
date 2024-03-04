<?php
/**
 * 路由类
 * @describe 获取请求的URL并匹配到对应的控制器和方法 返回一个数组给调度类处理，支持RESTful路由、支持路由表、支持路由参数、响应url和pathinfo、混杂模式、支持路由别名、支持路由分组、支持路由中间件、支持路由参数、支持路由别名、支持路由分组、支持路由中间件、支持路由参数、支持路由别名、支持路由分组、支持路由中间件、支持路由参数、支持路由别名、支持路由分组、支持路由中间件、支持路由参数、支持路由别名、支持路由分组、支持路由中间件
 * @package Snail
 * @author Imccc
 * @version 0.0.1
 * @copyright Copyright (c) 2024 Imccc
 * @license MIT
 * @link https://github.com/Imccc/Snail
 */

namespace Imccc\Snail\Core;

use Imccc\Snail\Core\Config;

class Router
{
    private $routes = [];

    // 路由规则
    public $patterns = [
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':all' => '.*',
        ':base64' => '[a-zA-Z0-9\/+=]+',
    ];

    // 请求的方法
    private $methodAllow = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD'];

    // 解析后的路由信息
    private $parsedRoute = [];

    // 构建方法
    public function __construct()
    {
        // 载入路由表
        $this->loadRoutes();
        // 路由匹配
        $this->match();
    }

    // 加载路由表
    private function loadRoutes()
    {
        $this->routes = Config::get('route');
    }

    // 解析路由配置数据
    private function parseHandler($handler)
    {
        if (is_callable($handler[0])) {
            // 如果是闭包函数
            return [
                'is_closure' => true,
                'closure' => $handler[0],
            ];
        } else {
            // 解析控制器、动作和命名空间
            list($method, $class) = explode('@', $handler[2]);
            $namespace = $handler[1];

            return [
                'is_closure' => false,
                'namespace' => $namespace,
                'controller' => $class,
                'action' => $method,
                'path' => isset($handler[0]) ? $handler[0] : '', // 修正此处的索引
                'method' => isset($handler[3]) ? $handler[3] : 'GET',
                'params' => isset($handler[4]) ? $handler[4] : [],
                'headers' => $this->getallheaders(),
            ];
        }
    }

    // 获取请求头
    public function getallheaders()
    {
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    // 路由匹配
    private function match()
    {
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        // 存储匹配到的路由规则信息数组
        $matchedRoutes = [];

        // 遍历路由配置
        foreach ($this->routes as $route => $handler) {
            // 解析路由配置数据
            $handler = $this->parseHandler($handler);
            $routePath = $route; // 使用路由配置的键作为路径
            $routeMethods = isset($handler['method']) ? explode('|', strtoupper($handler['method'])) : [];

            // 检查请求方法是否符合要求
            if (!empty($routeMethods) && !in_array($method, $routeMethods)) {
                continue;
            }

            // 将路由中的通配符替换为正则表达式
            // $pattern = '#^' . strtr($routePath, $this->patterns) . '$#';
            // 构建路由正则表达式
            $pattern = '#^' . preg_replace_callback('/:(\w+)/', function ($matches) use ($handler) {
                return isset($this->patterns[$matches[1]]) ? '(' . $this->patterns[$matches[1]] . ')' : '([^/]+)';
            }, $routePath) . '$#';

            // 尝试匹配当前路由规则
            if (preg_match($pattern, $uri, $matches)) {
                // 匹配成功，执行对应的处理程序
                array_shift($matches); // 移除匹配的第一项（完整匹配）
                $params = $matches; // 提取路由参数

                // 如果是闭包函数，直接执行
                if ($handler['is_closure']) {
                    call_user_func($handler['closure']);
                    exit();
                } else {
                    // 构建并存储匹配的路由信息数据
                    $routeInfo = [
                        'namespace' => $handler['namespace'] ?? '',
                        'controller' => $handler['controller'] ?? '',
                        'action' => $handler['action'] ?? '',
                        'params' => $params,
                        'method' => $method,
                        'headers' => $this->getallheaders(),
                    ];

                    // 存储匹配到的路由规则信息数组
                    $matchedRoutes = $routeInfo;
                }
            }
        }

        //如果没有匹配到输出404头
        if (empty($matchedRoutes)) {
            $this->parsedRoute = [];
        } else {
            // 有匹配到的路由信息数组
            $this->parsedRoute = $matchedRoutes;
        }

    }

    public function getRouteInfo()
    {
        return $this->parsedRoute;
    }
}
