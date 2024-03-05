<?php
namespace Imccc\Snail\Mvc;

class Controller
{
    protected $routes;

    public function __construct($routes)
    {
        $this->routes = $routes;
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
