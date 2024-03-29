<?php

namespace Imccc\Snail\Services\Drivers;

use Redis;

class RedisCacheDriver
{
    protected $redis;

    public function __construct($config)
    {
        $this->redis = new Redis();
        $this->redis->connect($config['host'], $config['port']);
        if (!empty($config['password'])) {
            $this->redis->auth($config['password']);
        }
        if (!empty($config['database'])) {
            $this->redis->select($config['database']);
        }
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }

    public function set($key, $value, $expiration = 0)
    {
        if ($expiration > 0) {
            return $this->redis->setex($key, $expiration, $value);
        } else {
            return $this->redis->set($key, $value);
        }
    }

    public function clear()
    {
        return $this->redis->flushDB();
    }
}
