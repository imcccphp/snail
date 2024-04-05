<?php
declare (strict_types = 1);

namespace Imccc\Snail;

defined('CONFIG_PATH') || define('CONFIG_PATH', dirname(__DIR__) . '/src/limbs/config');
defined('CFG_EXT') || define('CFG_EXT', '.conf.php');

use Imccc\Snail\Core\Config;
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
        // 配置
        $this->config = Config::get('snail.on');
        $this->initializeContainer();
        $this->run();
    }

    /**
     * 运行入口
     */
    public function run()
    {
        // 配置
        $this->config = Config::get('snail.on');
        set_error_handler([HandlerException::class, 'handleException']); // 注册全局异常处理函数
        $d = new Router(); //初始化路由
        $this->router = $d->getRouteInfo(); //获取路由信息
        $dispatch = new Dispatcher($this->router); //初始化分发器
        $dispatch->dispatch(); //分发
    }

    /**
     * 初始化服务容器并注册服务
     */
    protected function initializeContainer()
    {
        $this->container = Container::getInstance();

    }

    /**
     * 获取服务
     */
    public function getServices()
    {
        // 获取所有已经注册的服务
        $bindings = $this->container->getBindings();
        $alises = $this->container->getAliases();
        echo "-------------------------<br>";
        
        // 遍历输出每个服务的信息
        foreach ($bindings as $serviceName => $binding) {
            echo "Service Name: $serviceName > ";

            foreach ($alises as $aliasName => $alias) {
                if ($alias == $serviceName) {
                    echo "Alias: $aliasName<br>";
                }
            }
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
        if (Defined('START_TIME')) {
            echo '<br>Use Times:' . (microtime(true) - START_TIME) / 1000 . " MS <br>";
        }
        if ($this->config['container']) {
            $this->getServices();
        }

    }

}
