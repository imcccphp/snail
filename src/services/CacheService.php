<?php

namespace Imccc\Snail\Services;

use Imccc\Snail\Core\Config;
use Imccc\Snail\Core\Container;
use RuntimeException;

class CacheService
{
    protected $container;
    protected $config;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = Config::get('cache');
    }

    public function get($key)
    {
        return $this->getCacheDriver()->get($key);
    }

    public function set($key, $value, $expiration = 0)
    {
        return $this->getCacheDriver()->set($key, $value, $expiration);
    }

    public function clear()
    {
        return $this->getCacheDriver()->clear();
    }

    protected function getCacheDriver()
    {
        $driver = $this->config['driver'];
        $driverKey = 'cache.' . $driver;

        if (!$this->container->has($driverKey)) {
            switch ($driver) {
                case 'file':
                    $this->container->bind($driverKey, function () {
                        return new FileCacheDriver(Config::get('cache.file'));
                    });
                    break;
                case 'redis':
                    $this->container->bind($driverKey, function () {
                        return new RedisCacheDriver(Config::get('cache.redis'));
                    });
                    break;
                case 'memcached':
                    $this->container->bind($driverKey, function () {
                        return new MemcachedCacheDriver(Config::get('cache.memcached'));
                    });
                    break;
                case 'mongo':
                    $this->container->bind($driverKey, function () {
                        return new MongoCacheDriver(Config::get('cache.mongo'));
                    });
                    break;
                default:
                    throw new RuntimeException('Unsupported cache driver');
            }
        }

        return $this->container->get($driverKey);
    }
}
