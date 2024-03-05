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
    public function input()
    {

        print_r($this->routes);

        return $this;
    }
}
