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
    public function input($ps)
    {
        if (empty($ps) || !isset($ps)) {
            return $this->routes;
        } else {
            $params = explode('.', $ps);

            foreach ($params as $key => $value) {
                if (isset($this->routes[$value])) {
                    return $this->routes[$value];
                }
            }
        }

        return $this;
    }

}
