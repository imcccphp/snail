<?php
namespace Imccc\Snail\Mvc;

use Imccc\Snail\Core\Container;
use Imccc\Snail\Services\LoggerService;
use Imccc\Snail\Services\MailService;

class Controller
{
    protected $routes;

    protected $container;

    public function __construct($routes)
    {
        $this->routes = $routes;
        $this->container();
    }

    /**
     * 注册容器
     */
    public function container()
    {
        $this->container = new Container();

        // 注册邮件服务到容器中
        $mailService = $container->bind('MailService', function () {
            return new MailService();
        });

        // 注册日志服务到容器中
        $logService = $container->bind('LoggerService', function () {
            return new LoggerService();
        });
    }

    /**
     * 读取参数
     *
     * @param string $ps 参数键名（使用点分隔表示嵌套关系）
     * @return mixed 如果提供了参数，则返回对应的值，否则返回整个 $this->routes 数组
     */
    public function input(string $ps = ''): mixed
    {
        $alldata = $this->routes;

        // 检查是否提供了参数
        if (empty($ps) || !isset($ps)) {
            return $alldata;
        } else {
            $pm = explode('.', $ps);
            foreach ($pm as $val) {
                // 逐级深入数组
                if (isset($alldata[$val])) {
                    return $alldata[$val];
                }
            }
        }
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

}
