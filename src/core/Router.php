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
    protected $config;
    private $routes = []; // 路由表
    private $parsedRoute = []; // 解析后的路由信息
    private $def = [];

    protected $patterns = array(
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':all' => '.*',
    );

    protected $methods = array('GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD');
    protected $middleware = [];
    protected $supportedSuffixes = ['.do', '.html', '.snail'];

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
     * 通过当前请求的URL和请求方法来匹配路由表中的路由
     */
    private function match()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']); // 获取请求方法
        $uri = $this->getUri(); // 获取经过处理的请求URI

        foreach ($this->routes as $group => $routes) {
            foreach ($routes as $route => $handler) {
                // 为了避免内外层变量名冲突，遍历的路由变量改名为$routePattern
                $handler = $this->parseHandler($handler); // 解析路由处理程序
                $routeMethods = isset($handler['method']) ? explode('|', strtoupper($handler['method'])) : ['GET'];
                if (!in_array($method, $routeMethods)) {
                    continue; // 请求方法不匹配，跳过
                }

                $pattern = $this->buildPattern($route);
                if (preg_match($pattern, $uri, $matches)) {
                    array_shift($matches); // 删除完整匹配的部分
                    $this->parsedRoute = $this->buildParsedRoute($handler, $matches, $method);
                    return;
                }
            }
        }

        // 如果没有匹配到路由，尝试直接解析URI
        $this->parseDirectly($uri);

    }

    /**
     * 检查是否存在对应的真实文件
     */
    private function checkForStaticFile($uri)
    {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $uri;
        if ($this->fileExistsCached($filePath)) {
            // 如果文件存在，设置解析路由信息以反映找到的静态文件
            $this->parsedRoute = [
                'is_static' => true,
                'file_path' => $filePath,
                // 根据需要，可以添加更多与静态文件服务相关的信息
            ];
        } else {
            // 如果既没有找到匹配的路由，也没有找到静态文件，则视为404
            $this->parsedRoute = ['is_closure' => false, 'status' => 404];
        }
    }

    /**
     * 检查文件是否存在，使用缓存机制
     */
    private function fileExistsCached($filePath)
    {
        // 为简化示例，这里不实现真实的缓存逻辑，只做基本的文件存在检查
        return file_exists($filePath) && is_file($filePath);
    }

    /**
     * 无路由表直接解析地址
     * @param $handler
     * @return array
     */
    private function parseDirectly($uri)
    {

        // 如果没有找到匹配的路由，则尝试检查是否存在对应的真实文件
        $this->checkForStaticFile($this->getUri());

        // 使用'/'分割URI
        $segments = explode('/', $uri);

        // 约定前三个段为group, controller和action
        // 获取分组，如果URL没有指定分组，则使用缺省设置
        $group = $segments[0] ?? '';
        if (array_key_exists($group, $this->def['group'])) {
            $config = $this->def['group'][$group];
        } else {
            $config = $this->def['route']; // 使用全局缺省设置
            array_unshift($segments, ''); // 因为没有分组，所以调整segments，为后续解析做准备
        }

        $controller = isset($segments[1]) && $segments[1] ? ucfirst($segments[1]) : $config['controller'];
        $action = $segments[2] ?? $config['action'];
        $params = [];

        // 是否使用key2value模式
        if ($this->def['keyvalue']) {
            $params = [];
            for ($i = 3; $i < count($segments); $i += 2) {
                if (isset($segments[$i + 1])) {
                    $params[$segments[$i]] = $segments[$i + 1];
                }
            }
        } else {
            $params = array_slice($segments, 3);
        }

        // 可以根据需要调整这里的逻辑以适配实际的配置结构
        $middlewares = $config['middlewares'] ?? [];

        // 填充解析后的路由信息
        $this->parsedRoute = [
            'is_closure' => false,
            'namespace' => $config['namespace'],
            'controller' => $controller,
            'path' => $uri,
            'action' => $action,
            'params' => $params,
            'method' => $_SERVER['REQUEST_METHOD'],
            'middlewares' => $middlewares,
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
     * 获取处理后的URI
     * @return string 处理后的URI
     */
    private function getUri()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') : '';
        return $this->removeUrlSuffix($uri) ?: '/';
    }

    /**
     * 构建解析后的路由信息
     * @param array $handler 解析后的处理程序信息
     * @param array $params 从URI中解析出的参数
     * @param string $method HTTP请求方法
     * @return array 解析后的路由信息
     */
    private function buildParsedRoute($handler, $params, $method)
    {
        return [
            'is_closure' => $handler['is_closure'],
            'closure' => $handler['closure'] ?? null,
            'namespace' => $handler['namespace'] ?? '',
            'controller' => $handler['controller'] ?? '',
            'action' => $handler['action'] ?? '',
            'params' => $params,
            'method' => $method,
            'middlewares' => $handler['middlewares'] ?? [],
        ];
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
