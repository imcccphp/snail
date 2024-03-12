<?php
/**
 * 路由类
 * @describe 获取请求的URL并匹配到对应的控制器和方法 返回一个数组给调度类处理，
 * 支持RESTful路由、支持路由表、支持路由参数、响应url和pathinfo、混杂模式、支持路由别名、
 * 支持路由分组、支持路由中间件、支持路由参数、支持路由别名、支持路由分组、支持路由中间件
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

    private $middlewareGroups = []; // 中间件组

    private $patterns = [ // 路由参数的正则表达式模式
        ':any' => '[^/]+', // 匹配任意字符（除了斜杠）
        ':num' => '[0-9]+', // 匹配数字
        ':all' => '.*', // 匹配所有字符
        ':base64' => '[a-zA-Z0-9\/+=]+', // 匹配base64编码字符
    ];

    public function __construct($routes = null)
    {
        $this->loadRoutes($routes); // 载入路由表
        $this->match(); // 匹配路由
    }

    /**
     * 载入路由表
     * @param array $routes 路由表
     */
    private function loadRoutes($routes = null)
    {
        if ($routes !== null) {
            $this->routes = $routes; // 如果提供了路由表，则使用提供的路由表
        } else {
            $this->routes = Config::get('route'); // 否则从配置中获取路由表
        }
    }

    /**
     * 匹配路由
     */
    private function parseHandler($handler)
    {
        if (is_callable($handler[0])) { // 如果路由处理程序是闭包函数
            return [
                'is_closure' => true, // 标记为闭包函数
                'closure' => $handler[0], // 记录闭包函数
            ];
        } else { // 否则，路由处理程序是控制器方法
            list($method, $class) = explode('@', $handler[2]); // 解析控制器方法
            $namespace = $handler[1]; // 控制器命名空间
            $middlewares = isset($handler['middlewares']) ? $handler['middlewares'] : []; // 中间件

            return [
                'is_closure' => false, // 标记为控制器方法
                'namespace' => $namespace, // 记录命名空间
                'controller' => $class, // 记录控制器类名
                'action' => $method, // 记录方法名
                'path' => isset($handler[0]) ? $handler[0] : '', // 记录路径
                'method' => isset($handler[3]) ? $handler[3] : 'GET', // 记录允许的请求方法，默认为GET
                'params' => isset($handler[4]) ? $handler[4] : [], // 记录路由参数
                'middlewares' => $middlewares, // 记录中间件
            ];
        }
    }

    /**
     * 匹配路由
     */
    private function match()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') : ''; // 获取请求的URI

        if ($uri === '') {
            $uri = '/'; // 如果URI为空，则设置为根路径
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD']); // 获取请求方法

        foreach ($this->routes as $routeGroup) { // 遍历路由分组
            foreach ($routeGroup as $route => $handler) { // 遍历每个路由组中的路由
                $handler = $this->parseHandler($handler); // 解析路由处理程序
                $routePath = $route; // 路由路径
                $routeMethods = isset($handler['method']) ? explode('|', strtoupper($handler['method'])) : []; // 允许的请求方法

                if (!empty($routeMethods) && !in_array($method, $routeMethods)) {
                    continue; // 如果请求方法不匹配，则跳过当前路由
                }

                $pattern = '#^' . preg_replace_callback('/:(\w+)/', function ($matches) {
                    return isset($this->patterns[$matches[1]]) ? '(' . $this->patterns[$matches[1]] . ')' : '([^/]+)';
                }, $routePath) . '$#'; // 构建路由正则表达式

                if (preg_match($pattern, $uri, $matches)) { // 尝试匹配当前路由规则
                    array_shift($matches); // 移除匹配的第一项（完整匹配）
                    $params = $matches; // 提取路由参数

                    if ($handler['is_closure']) { // 如果是闭包函数
                        $this->parsedRoute = [
                            'is_closure' => true,
                            'result' => $handler['closure'](...$params), // 执行闭包函数并传入参数
                        ];
                        return; // 匹配到闭包函数后立即返回，不再继续匹配其他路由规则
                    } else { // 否则是控制器方法
                        $routeInfo = [
                            'namespace' => $handler['namespace'] ?? '', // 命名空间
                            'controller' => $handler['controller'] ?? '', // 控制器类名
                            'action' => $handler['action'] ?? '', // 方法名
                            'params' => $params, // 路由参数
                            'method' => $method, // 请求方法
                            'middlewares' => $handler['middlewares'], // 中间件
                        ];

                        $this->parsedRoute = $routeInfo; // 记录匹配到的路由信息
                        return;
                    }
                }
            }
        }

        $this->parsedRoute = ['404']; // 如果没有匹配到任何路由，则返回404
    }

    /**
     * 获取解析后的路由信息
     * @return array 解析后的路由信息
     */
    public function getRouteInfo()
    {
        return $this->parsedRoute; // 获取解析后的路由信息
    }
}
