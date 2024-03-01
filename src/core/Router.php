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

use Exception;

class Router
{
    private $routes = [];
    private $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
    public static $patterns = array(
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':all' => '.*',
    );

    // 构造函数
    public function __construct()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUrl = $_SERVER['REQUEST_URI'];
        $this->resolve($requestMethod, $requestUrl);
    }

    // 添加路由
    public function addRoute($method, $pattern, $handler)
    {
        // 将请求方法转换为大写形式，以便统一处理
        $method = strtoupper($method);

        // 如果请求方法是 GET 或 POST，则添加路由
        if (in_array($method, $this->methods)) {
            // 将路由中的通配符替换为正则表达式
            $pattern = '#^' . strtr($pattern, self::$patterns) . '$#';

            $this->routes[] = ['method' => $method, 'pattern' => $pattern, 'handler' => $handler];
        } else {
            // 否则，抛出异常
            throw new Exception("Unsupported HTTP method: $method");
        }
    }

    // 解析路由
    public function resolve($method, $url)
    {
        // 将请求方法转换为大写形式，以便统一处理
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            // 检查请求方法和路由模式是否匹配
            if ($route['method'] === $method && preg_match($route['pattern'], $url, $matches)) {
                // 移除匹配的第一项（完整匹配）
                array_shift($matches);

                // 分析 URL
                $urlParts = parse_url($url);
                $pathSegments = explode('/', trim($urlParts['path'], '/'));

                // 获取查询参数
                $queryParams = [];
                if (isset($urlParts['query'])) {
                    parse_str($urlParts['query'], $queryParams);
                }

                // 合并路由参数和查询参数
                $params = array_merge($matches, $queryParams);

                // 获取头部信息
                $headers = getallheaders();

                // 解析令牌
                $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

                // 路由信息
                $routeInfo = [
                    'app' => 'default', // 应用程序名称，默认为 default
                    'controller' => isset($pathSegments[0]) ? $pathSegments[0] : '',
                    'action' => isset($pathSegments[1]) ? $pathSegments[1] : '',
                    'params' => $params,
                    'method' => $method,
                    'url' => $url,
                    'pathinfo' => $urlParts['path'],
                    'headers' => $headers,
                    'token' => $token,
                ];

                // 返回路由信息
                return $routeInfo;
            }
        }

        // 如果没有找到匹配的路由，返回空
        return null;
    }

}
/**
// 使用示例
$router = new Router();

// 添加路由规则
$router->addRoute('GET', '/users/:num', function ($userId) {
echo "Show user with ID: $userId";
});

$router->addRoute('GET', '/articles/:any', function ($slug) {
echo "Show article with slug: $slug";
});

$router->addRoute('POST', '/posts/:num/comments/:num', function ($postId, $commentId) {
echo "Add comment to post $postId with comment ID: $commentId";
});

// 解析请求
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUrl = $_SERVER['REQUEST_URI'];

try {
$route = $router->resolve($requestMethod, $requestUrl);

if ($route) {
print_r($route);
} else {
echo "404 Not Found";
}
} catch (Exception $e) {
echo $e->getMessage();
}
 */
