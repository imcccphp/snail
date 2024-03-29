<?php
namespace Imccc\Snail\Mvc;

use Imccc\Snail\Core\Config;
use Imccc\Snail\Core\Container;
use Imccc\Snail\Services\LoggerService;
use Imccc\Snail\Services\MailService;
use RuntimeException;

class Controller
{
    protected $config; // 配置信息
    protected $routes; // 用于存储路由信息
    protected $container; // 服务容器，用于依赖注入

    /**
     * 构造函数
     *
     * @param array $routes 路由数组
     */
    public function __construct($routes)
    {
        $this->routes = $routes;
        $this->initializeContainer();
    }

    /**
     * 初始化服务容器并注册服务
     */
    protected function initializeContainer()
    {
        $this->container = Container::getInstance();
        // 注册配置服务
        $this->container->bind('ConfigService', function () {
            return new ConfigService();
        });

        $config = $container->resolve('ConfigService');

        $this->config = $config->get('snail.on');

        // 注册日志服务
        if ($this->config['log']) {
            $this->container->bind('LoggerService', function () {
                return new LoggerService();
            });
        }

        // 注册邮件服务
        if ($this->config['mail']) {
            $this->container->bind('MailService', function () {
                return new MailService();
            });
        }

        // 注册缓存服务
        if ($this->config['cache']) {
            $this->container->bind('CacheService', function () {
                return new CacheService();
            });
        }

        // 注册sql服务
        if ($this->config['sql']) {
            $this->container->bind('SqlService', function () {
                return new SqlService();
            });
        }
    }

    /**
     * 根据点分隔的键名读取请求参数
     *
     * @param string $ps 点分隔的参数键名
     * @return mixed 参数值或者null
     */
    public function input(string $ps = ''): mixed
    {
        if (empty($ps)) {
            return $this->routes;
        }

        $keys = explode('.', $ps);
        $value = $this->routes;

        // 按点分隔的键名逐层查找
        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null; // 没找到指定的键名时返回null
            }
        }

        return $value;
    }

    /**
     * 获取POST请求体中的数据
     *
     * 根据Content-Type处理不同格式的请求体
     *
     * @return mixed 解析后的数据
     * @throws RuntimeException 解析错误时抛出异常
     */
    public function getPost(): mixed
    {
        // 非POST请求返回空数组
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [];
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $rawData = file_get_contents('php://input');

        switch (true) {
            case strpos($contentType, 'application/json') !== false:
                $data = json_decode($rawData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException('JSON 解析错误');
                }
                return $data;

            case strpos($contentType, 'application/x-www-form-urlencoded') !== false:
                parse_str($rawData, $data);
                return $data;

            case strpos($contentType, 'application/xml') !== false:
                $data = simplexml_load_string($rawData);
                if ($data === false) {
                    throw new RuntimeException('XML 解析错误');
                }
                return (array) $data;

            default:
                return []; // 不支持的格式或无数据时返回空数组
        }
    }

    /**
     * 获取所有HTTP请求头信息
     *
     * @return array 包含所有请求头信息的关联数组
     */
    public function getallheaders(): array
    {
        $headers = [];
        // 遍历$_SERVER数组，提取HTTP头信息
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}
