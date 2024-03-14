<?php

namespace Imccc\Snail\Core;

interface MiddlewareInterface
{
    public function handle($next);
}
