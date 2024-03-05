<?php
namespace Imccc\Snail\Mvc;

use Imccc\Snail\Core\Container;
use Imccc\Snail\Services\LoggerService;
use Imccc\Snail\Services\MailService;

class Controller
{
    protected $routes;

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
        $container = new Container();

        // 注册邮件服务到容器中
        $mailService = $container->bind('MailService', function ($container) {
            return new MailService();
        });

        // 注册日志服务到容器中
        $logService = $container->bind('LoggerService', function ($container) {
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

}
