<?php
declare (strict_types = 1);

namespace Imccc\Snail;

defined('CONFIG_PATH') || define('CONFIG_PATH', dirname(__DIR__) . '/src/limbs/config');
defined('CFG_EXT') || define('CFG_EXT', '.conf.php');

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
     * 销毁
     */
    public function __destruct()
    {
        echo '<br>Times:' . (microtime(true) - START) / 1000 . "ms";
    }

}
