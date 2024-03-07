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
    private $routes = [];

    // 中间件
    private $middlewares = [];

    // 路由规则
    public $patterns = [
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':all' => '.*',
        ':base64' => '[a-zA-Z0-9\/+=]+',
    ];

    // 请求的方法
    private $methodAllow = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD'];

    // 定义支持的URL后缀
    private $supportedSuffixes = ['.do', '.html'];

    // 解析后的路由信息
    private $parsedRoute = [];

    // 中间件
    private $middlewareGroups = [];

    // 构建方法
    public function __construct()
    {
        // 载入路由表
        $this->loadRoutes();
        // 路由匹配
        $this->match();
    }

    /**
     * 载入路由表
     * @throws \Exception
     */
    private function loadRoutes()
    {
        $this->routes = Config::get('route');
    }

    /**
     * 解析路由
     * @param $handler
     * @return array
     * @throws \Exception
     */
    private function parseHandler($handler, $params)
    {
        if (is_callable($handler[0])) {
            // 如果是闭包函数，使用 use 关键字传递参数
            return [
                'is_closure' => true,
                'closure' => $handler[0],
                'params' => $params,
            ];
        } else {
            // 解析控制器、动作和命名空间
            list($method, $class) = explode('@', $handler[2]);
            $namespace = $handler[1];

            // 添加检查中间件是否存在的条件
            $middlewares = isset($handler['middlewares']) ? $handler['middlewares'] : [];

            return [
                'is_closure' => false,
                'namespace' => $namespace,
                'controller' => $class,
                'action' => $method,
                'path' => isset($handler[0]) ? $handler[0] : '', // 修正此处的索引
                'method' => isset($handler[3]) ? $handler[3] : 'GET',
                'params' => isset($handler[4]) ? $handler[4] : [],
                'middlewares' => $middlewares, // 添加中间件
                'headers' => $this->getallheaders(),
                'files' => $_FILES,
                'postbody' => $this->getPost(),
            ];
        }
    }

    /**
     * 匹配路由
     */
    private function match()
    {
        // 获取请求的 URI，并确保不为空
        $uri = isset($_SERVER['REQUEST_URI']) ? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') : '';

        // 如果 URI 为空，则将其设置为根目录路径
        if ($uri === '') {
            $uri = '/';
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        // 存储匹配到的路由规则信息数组
        $matchedRoutes = [];

        // 移除URL后缀，如果有的话
        $uri = $this->removeUrlSuffix($uri);

        // 遍历路由配置
        foreach ($this->routes as $route => $handler) {
            // 解析路由配置数据，并传递路由参数
            $handler = $this->parseHandler($handler, [$uri]);
            $routePath = $route; // 使用路由配置的键作为路径
            $routeMethods = isset($handler['method']) ? explode('|', strtoupper($handler['method'])) : [];

            // 检查请求方法是否符合要求
            if (!empty($routeMethods) && !in_array($method, $routeMethods)) {
                continue;
            }

            // 构建路由正则表达式
            $pattern = '#^' . preg_replace_callback('/:(\w+)/', function ($matches) use ($handler) {
                return isset($this->patterns[$matches[1]]) ? '(' . $this->patterns[$matches[1]] . ')' : '([^/]+)';
            }, $routePath) . '$#';

            // 尝试匹配当前路由规则
            if (preg_match($pattern, $uri, $matches)) {
                // 匹配成功，执行对应的处理程序
                array_shift($matches); // 移除匹配的第一项（完整匹配）
                $params = $matches; // 提取路由参数

                // 添加中间件处理
                foreach ($handler['middlewares'] as $middleware) {
                    $middlewareInstance = new $middleware();
                    $params = $middlewareInstance->handle($params);
                }

                // 如果是闭包函数,返回闭包函数和参数
                if ($handler['is_closure']) {
                    $routeInfo = [
                        'is_closure' => true,
                        'closure' => $handler['closure'],
                    ];
                } else {
                    // 构建并存储匹配的路由信息数据
                    $routeInfo = [
                        'namespace' => $handler['namespace'] ?? '',
                        'controller' => $handler['controller'] ?? '',
                        'action' => $handler['action'] ?? '',
                        'params' => $params,
                        'method' => $method,
                        'headers' => $this->getallheaders(),
                        'middlewares' => $handler['middlewares'], // 添加中间件
                        'files' => $_FILES,
                        'postbody' => $this->getPost(),
                    ];

                    // 存储匹配到的路由规则信息数组
                    $matchedRoutes = $routeInfo;
                }
            }
        }

        //如果没有匹配到返回404
        if (empty($matchedRoutes)) {
            $this->parsedRoute = ['404'];
        } else {
            // 有匹配到的路由信息数组
            $this->parsedRoute = $matchedRoutes;
        }

    }

    /**
     * 移除URL后缀
     *
     * @param string $uri
     * @return string
     */
    private function removeUrlSuffix($uri)
    {
        // 检查并移除支持的后缀
        foreach ($this->supportedSuffixes as $suffix) {
            if (substr($uri, -strlen($suffix)) === $suffix) {
                $uri = substr($uri, 0, -strlen($suffix));
                break; // 找到一个后缀就退出循环
            }
        }
        return $uri;
    }

    /**
     * 获取所有 HTTP 头信息
     *
     * @return array 包含所有 HTTP 头信息的数组
     * @throws RuntimeException 如果获取失败
     */
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

    /**
     * 获取 POST 请求中的数据，并进行验证
     *
     * @param array $rules 验证规则，格式为 ['字段名' => '规则']
     * @return array 包含验证通过的 POST 数据的关联数组，如果验证失败返回空数组
     * @throws RuntimeException 如果规则中指定的字段不存在
     */
    public function getPost()
    {
        // 检查请求方法是否为 POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [];
        }

        // 获取 POST 数据
        $rawData = file_get_contents('php://input');
        if (empty($rawData)) {
            return [];
        }

        // 尝试解析 JSON 数据
        $jsonData = json_decode($rawData, true);
        if ($jsonData !== null && json_last_error() === JSON_ERROR_NONE) {
            return $jsonData;
        }

        // 尝试解析 URL 编码数据
        parse_str($rawData, $parsedData);
        if (!empty($parsedData)) {
            return $parsedData;
        }

        // 尝试解析 XML 数据
        $xmlData = @simplexml_load_string($rawData);
        if ($xmlData !== false) {
            return $xmlData;
        }

        // 默认情况下，返回原始数据
        return $rawData;
    }

    /**
     * 获取解析后的路由信息
     *
     * @return array 包含解析后的路由信息的数组
     */
    public function getRouteInfo()
    {
        return $this->parsedRoute;
    }
}
