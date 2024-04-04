<?php
declare (strict_types = 1);

namespace Imccc\Snail;

defined('CONFIG_PATH') || define('CONFIG_PATH', dirname(__DIR__) . '/src/limbs/config');
defined('CFG_EXT') || define('CFG_EXT', '.conf.php');

use Imccc\Snail\Core\Container;
use Imccc\Snail\Core\Dispatcher;
use Imccc\Snail\Core\HandlerException;
use Imccc\Snail\Core\Router;

class Snail
{
    const SNAIL = 'Snail';
    const SNAIL_VERSION = '0.0.1';

    protected $router;
    protected $config;
    protected $logger;
    protected $container;

    public function __construct()
    {
        $this->initializeContainer();
        $this->run();
    }

    /**
     * 运行入口
     */
    public function run()
    {
        set_error_handler([HandlerException::class, 'handleException']); // 注册全局异常处理函数
        $d = new Router($this->container); //初始化路由
        $this->router = $d->getRouteInfo(); //获取路由信息
        $dispatch = new Dispatcher($this->container, $this->router); //初始化分发器
        $dispatch->dispatch(); //分发
    }

    /**
     * 初始化服务容器并注册服务
     */
    protected function initializeContainer()
    {
        $this->container = Container::getInstance();
        // // 注册配置服务
        // $this->container->bind('ConfigService', function () {
        //     return new ConfigService($this->container);
        // });

        // // 注册SQL服务
        // $this->container->bind('SqlService', function () {
        //     return new SqlService($this->container);
        // });

        // // 配置服务
        // $config = $this->container->resolve('ConfigService');

        // // 配置
        // $this->config = $config->get('snail.on');

        // // 注册日志服务
        // if ($this->config['log']) {
        //     $this->container->bind('LoggerService', function () {
        //         return new LoggerService($this->container);
        //     });
        // }

        // // 日志服务
        // $this->logger = $this->container->resolve('LoggerService');

    }

    /**
     * 获取服务
     */
    public function getServices()
    {
        // 获取所有已经注册的服务
        $bindings = $this->container->getBindings();
        // 遍历输出每个服务的信息
        foreach ($bindings as $serviceName => $binding) {
            echo "Service Name: $serviceName<br>";
            // 检查具体实现类是否为闭包
            if ($binding['concrete'] instanceof Closure) {
                echo "Concrete: Closure<br>";
            } else {
                echo "Concrete: " . (is_object($binding['concrete']) ? get_class($binding['concrete']) : $binding['concrete']) . "<br>";
            }
            echo "Shared: " . ($binding['shared'] ? 'Yes' : 'No') . "<br>";
            echo "-------------------------<br>";
        }

    }

    /**
     * 销毁
     */
    public function __destruct()
    {
        $this->logger->log('Snail Run Success');
        if (Defined('START_TIME')) {
            echo '<br>Use Times:' . (microtime(true) - START_TIME) / 1000 . " MS";
        }
        if ($this->config['container']) {
            $this->getServices();
        }

    }

}
