<?php

namespace Imccc\Snail\Core;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use ReflectionMethod;

class Container implements ContainerInterface
{
    protected $bindings = []; // 绑定列表，存储服务标识符和具体实现的映射关系
    protected $instances = []; // 实例列表，存储已解析的服务实例

    /**
     * 绑定一个服务到容器
     *
     * @param string $id 服务标识符
     * @param mixed $concrete 具体实现（可以是闭包、类名、方法或实例）
     * @param bool $shared 是否共享实例
     * @return $this 当前容器实例
     * @throws InvalidArgumentException 如果提供的具体实现类型无效
     */
    public function bind(string $id, $concrete = null, bool $shared = false): self
    {
        if (!$this->isValidConcrete($concrete)) {
            throw new \InvalidArgumentException("Invalid concrete type provided for [$id].");
        }

        $this->bindings[$id] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];

        return $this;
    }

    /**
     * 获取指定标识符的服务实例
     *
     * @param string $id 服务标识符
     * @return mixed 服务实例
     * @throws NotFoundException 如果服务不存在
     */
    public function get($id)
    {
        // 如果实例已经存在，则直接返回
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 如果服务不存在，则抛出异常
        if (!$this->has($id)) {
            throw new \NotFoundException("Service '$id' not found.");
        }

        $binding = $this->bindings[$id];

        // 根据绑定类型解析具体实现
        if (isset($binding['concrete'])) {
            $concrete = $binding['concrete'];
            $instance = $this->build($concrete);

            // 如果设置为共享实例，则保存到实例列表中
            if ($binding['shared']) {
                $this->instances[$id] = $instance;
            }

            return $instance;
        }

        return null;
    }

    /**
     * 检查容器中是否存在指定标识符的服务
     *
     * @param string $id 服务标识符
     * @return bool 如果服务存在则返回true，否则返回false
     */
    public function has($id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    /**
     * 解析闭包或方法，支持参数注入
     *
     * @param Closure|callable $callback 闭包或方法
     * @param array $parameters 参数列表
     * @return mixed 闭包或方法执行结果
     */
    protected function call($callback, array $parameters = [])
    {
        // 创建反射对象
        if ($callback instanceof Closure) {
            $reflector = new ReflectionFunction($callback);
        } else {
            $reflector = new ReflectionMethod($callback[0], $callback[1]);
        }

        $dependencies = [];

        // 解析参数依赖
        foreach ($reflector->getParameters() as $parameter) {
            if (isset($parameters[$parameter->getName()])) {
                // 如果参数已经提供，则直接使用
                $dependencies[] = $parameters[$parameter->getName()];
            } elseif ($parameter->getClass()) {
                // 如果参数是类类型，则递归解析
                $dependencies[] = $this->get($parameter->getClass()->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                // 如果参数有默认值，则使用默认值
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                // 否则无法解析依赖
                throw new \RuntimeException("Unable to resolve dependency '{$parameter->getName()}'.");
            }
        }

        // 执行闭包或方法，并传入解析后的参数
        return $callback instanceof Closure ? $callback(...$dependencies) : $callback[0]->{$callback[1]}(...$dependencies);
    }

    /**
     * 绑定一个服务到容器并设置为共享实例
     *
     * @param string $id 服务标识符
     * @param mixed $concrete 具体实现（可以是闭包、类名、方法或实例）
     * @return $this 当前容器实例
     * @throws InvalidArgumentException 如果提供的具体实现类型无效
     */
    public function singleton(string $id, $concrete = null): self
    {
        return $this->bind($id, $concrete, true);
    }

    /**
     * 绑定一个已存在的实例到容器
     *
     * @param string $id 服务标识符
     * @param mixed $instance 实例
     * @return $this 当前容器实例
     */
    public function instance(string $id, $instance): self
    {
        // 将实例保存到实例列表中
        $this->instances[$id] = $instance;
        return $this;
    }

    /**
     * 检查给定的具体实现是否有效
     *
     * @param mixed $concrete 具体实现
     * @return bool 如果具体实现有效则返回true，否则返回false
     */
    protected function isValidConcrete($concrete): bool
    {
        // 检查具体实现是否是闭包、类名、可调用实例或对象
        return $concrete instanceof Closure ||
        is_string($concrete) && class_exists($concrete) ||
        is_callable($concrete) ||
        is_object($concrete);
    }
}

/**
 * 使用方法
use Imccc\Snail\Core\Container;

// 创建容器实例
$container = new Container();

// 示例 1: 绑定类到接口，并获取实例
$container->bind('SomeInterface', 'SomeImplementation');
$instance = $container->get('SomeInterface');

// 示例 2: 绑定闭包并获取实例
$container->bind('example', function () {
return new Example();
});
$instance = $container->get('example');

// 示例 3: 绑定方法到类并获取实例
$container->bind('Example', 'ExampleClass');
$instance = $container->get('Example');
$instance->method();

// 示例 4: 绑定单例并获取共享实例
$container->singleton('Singleton', function () {
return new Singleton();
});
$instance1 = $container->get('Singleton');
$instance2 = $container->get('Singleton');
var_dump($instance1 === $instance2); // 输出: true，两次获取的是同一个实例

// 示例 5: 绑定一个已存在的实例到容器
$instance = new ExistingInstance();
$container->instance('ExistingInstance', $instance);
$retrievedInstance = $container->get('ExistingInstance');
var_dump($instance === $retrievedInstance); // 输出: true，获取的是同一个实例

 */
