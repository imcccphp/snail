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
        set_error_handler([HandlerException::class, 'handleException']);
        $d = new Router();

        $this->router = $d->getRouteInfo();
        // print_r($d->getRouteInfo());
        $dispatch = new Dispatcher($this->router);
        $dispatch->dispatch();
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
    }

    /**
     * 销毁
     */
    public function __destruct()
    {
        if (Defined('START_TIME')) {
            echo '<br>Use Times:' . (microtime(true) - START_TIME) / 1000 . " MS";
        }
    }

}
