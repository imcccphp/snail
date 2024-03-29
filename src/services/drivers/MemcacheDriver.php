<?php

namespace Imccc\Snail\Services\Drivers;

use Memcached;

class MemcachedCacheDriver
{
    protected $memcached;

    public function __construct($config)
    {
        $this->memcached = new Memcached();
        $this->memcached->addServer($config['host'], $config['port']);
    }

    public function get($key)
    {
        return $this->memcached->get($key);
    }

    public function set($key, $value, $expiration = 0)
    {
        return $this->memcached->set($key, $value, $expiration);
    }

    public function clear()
    {
        return $this->memcached->flush();
    }
}
