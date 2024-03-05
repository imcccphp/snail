<?php
namespace Imccc\Snail\Mvc;

class Controller
{
    protected $routes;

    public function __construct($routes)
    {
        $this->routes = $routes;
    }

    //
    public function input($ps = '')
    {
        $alldata = $this->routes;
        if (empty($ps) || !isset($ps)) {
            return $alldata;
        } else {
            $pm = explode('.', $ps);
            foreach ($pm as $val) {
                if ($f == $val) {
                    unset($pm[$val]); //移除文件名
                } else {
                    if (isset($alldata[$val])) {
                        $alldata = $alldata[$val];
                    }
                }
            }
            return $alldata;

        }

        return $this;
    }

}
