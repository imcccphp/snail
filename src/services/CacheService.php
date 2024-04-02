<?php

namespace Imccc\Snail\Services;

use Imccc\Snail\Core\Config;
use Imccc\Snail\Core\Container;
use Imccc\Snail\Services\Drivers\FileCacheDriver;
use Imccc\Snail\Services\Drivers\MemcachedCacheDriver;
use Imccc\Snail\Services\Drivers\MongoCacheDriver;
use Imccc\Snail\Services\Drivers\RedisCacheDriver;
use RuntimeException;

class CacheService
{
    protected $container;
    protected $config;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $this->container->resolve('ConfigService')->get('cache');
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
                        return new FileCacheDriver($container);
                    });
                    break;
                case 'redis':
                    $this->container->bind($driverKey, function () {
                        return new RedisCacheDriver($container);
                    });
                    break;
                case 'memcached':
                    $this->container->bind($driverKey, function () {
                        return new MemcachedCacheDriver($container);
                    });
                    break;
                case 'mongo':
                    $this->container->bind($driverKey, function () {
                        return new MongoCacheDriver($container);
                    });
                    break;
                default:
                    throw new RuntimeException('Unsupported cache driver');
            }
        }

        return $this->container->get($driverKey);
    }
}
