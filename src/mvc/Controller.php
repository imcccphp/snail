<?php
namespace Imccc\Snail\Mvc;

use RuntimeException;

class Controller
{
    protected $config; // 配置信息
    protected $routes; // 用于存储路由信息

    /**
     * 构造函数
     *
     * @param array $routes 路由数组
     */
    public function __construct($routes)
    {
        $this->routes = $routes;
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
