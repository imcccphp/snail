<?php
namespace Imccc\Snail\Mvc;

class Controller
{
    protected $disp;

    public function __construct($disp)
    {
        $this->disp = $disp;
    }

    //
    public function input()
    {

        print_r($this->disp);

        return $this;
    }
}
