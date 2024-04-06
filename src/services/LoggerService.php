<?php
declare (strict_types = 1);

namespace Imccc\Snail;

defined('CONFIG_PATH') || define('CONFIG_PATH', dirname(__DIR__) . '/src/limbs/config');
defined('CFG_EXT') || define('CFG_EXT', '.conf.php');
defined('START_TIME') || define('START_TIME', microtime(true));

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
        register_shutdown_function([$this, 'pushlog']);
    }

    /**
     * 运行入口
     */
    public function run()
    {
        // 注册全局异常处理函数
        set_error_handler([HandlerException::class, 'handleException']);

        //初始化路由
        $d = new Router($this->container);

        //获取路由信息
        $this->router = $d->getRouteInfo();

        //初始化分发器
        $dispatch = new Dispatcher($this->container, $this->router);

        //分发
        $dispatch->dispatch();
    }

    /**
     * 初始化服务容器并注册服务
     */
    protected function initializeContainer()
    {
        $this->container = Container::getInstance();

        // 配置服务
        $config = $this->container->resolve('ConfigService');

        // 配置
        $this->config = $config->get('snail.on');

        // 日志服务
        $this->logger = $this->container->resolve('LoggerService');

    }

    /**
     * 获取服务
     */
    public function getServices()
    {
        // 获取所有已经注册的服务
        $bindings = $this->container->getBindings();
        $alises = $this->container->getAliases();
        $info = "-------------------------<br>";

        // 遍历输出每个服务的信息
        foreach ($bindings as $serviceName => $binding) {
            $info .= "Service Name: $serviceName > ";

            $info .= "Aliases: " . $alises[$serviceName] . "<br>" ?? '' . "<br>";

            // 检查具体实现类是否为闭包
            if ($binding['concrete'] instanceof Closure) {
                $info .= "Concrete: Closure<br>";
            } else {
                $info .= "Concrete: " . (is_object($binding['concrete']) ? get_class($binding['concrete']) : $binding['concrete']) . "<br>";
            }
            $info .= "Shared: " . ($binding['shared'] ? 'Yes' : 'No') . "<br>";
            $info .= "-------------------------<br>";
        }
        return $info;
    }

    public function pushlog()
    {
        $this->logger->log('Snail Run Success. Use Times:' . (microtime(true) - START_TIME) / 1000 . " ms");
    }

    /**
     * 销毁
     */
    public function __destruct()
    {
        if ($this->confi['usetime']) {
            echo '<br>Use Times:' . (microtime(true) - START_TIME) / 1000 . " ms. <br>";
        }
        if ($this->config['container']) {
            if ($this->config['debug']) {
                $this->logger->log('Services:' . $this->getServices());
            }
            echo $this->getServices();
        }

    }

}
