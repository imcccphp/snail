<?php
/**
 * 容器类
 *
 * @package Imccc\Snail
 * @since 0.0.1
 * @author Imccc
 * @copyright Copyright (c) 2024 Imccc.
 */

namespace Imccc\Snail\Core;

use Closure;
use Exception;
use ReflectionClass;

class Container
{
    protected $bindings = []; // 绑定列表
    protected $lastBound = ''; // 最后绑定的接口或抽象类

    /**
     * 绑定接口或抽象类到具体实现类
     *
     * @param string $abstract 接口或抽象类名
     * @param mixed $concrete 具体实现类名、闭包或实例
     * @param bool $shared 是否共享实例
     * @return $this 当前容器实例
     * @throws Exception 如果提供的具体实现类类型无效
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): self
    {
        // 确保提供的具体实现类类型有效
        if (!is_string($concrete) && !$concrete instanceof Closure && !is_object($concrete)) {
            throw new Exception("Invalid concrete type provided for [$abstract].");
        }

        // 存储绑定信息
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract, // 如果没有提供具体实现类，则默认与接口名一致
            'shared' => $shared, // 是否共享实例
            'instance' => null, // 共享实例
        ];

        // 记录最后绑定的接口或抽象类
        $this->lastBound = $abstract;

        return $this;
    }

    /**
     * 获取绑定的实例
     *
     * @param string $abstract 接口或抽象类名
     * @return mixed 具体实现类的实例
     * @throws Exception 当绑定不存在时抛出异常
     */
    public function make(string $abstract)
    {
        // 检查绑定是否存在
        if (!isset($this->bindings[$abstract])) {
            throw new Exception("Service '$abstract' not found.");
        }

        // 获取绑定信息
        $binding = $this->bindings[$abstract];

        // 如果是共享实例且已经存在，则直接返回
        if ($binding['shared'] && $binding['instance'] !== null) {
            return $binding['instance'];
        }

        // 解析依赖并创建实例
        $concrete = $binding['concrete'];
        $instance = $this->build($concrete);

        // 如果是共享实例，则保存到 bindings 中
        if ($binding['shared']) {
            $this->bindings[$abstract]['instance'] = $instance;
        }

        return $instance;
    }

    /**
     * 创建具体实现类的新实例
     *
     * @param mixed $concrete 具体实现类名、闭包或实例
     * @return mixed 具体实现类的实例
     * @throws Exception 当实例化失败时抛出异常
     */
    protected function build($concrete)
    {
        // 如果是闭包，则调用闭包
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        // 否则尝试实例化具体实现类
        $reflector = new ReflectionClass($concrete);

        // 检查是否可实例化
        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$concrete] is not instantiable.");
        }

        // 获取构造函数参数
        $constructor = $reflector->getConstructor();

        // 如果没有构造函数，则直接实例化
        if ($constructor === null) {
            return new $concrete;
        }

        // 否则解析构造函数参数的依赖关系
        $dependencies = $this->resolveDependencies($constructor->getParameters());

        // 创建实例并传入依赖
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * 解析构造函数参数的依赖关系
     *
     * @param array $parameters 构造函数参数列表
     * @return array 构造函数参数的实例列表
     * @throws Exception 当无法解析依赖时抛出异常
     */
    protected function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            // 如果参数不是类类型，则检查是否有默认值
            if ($dependency === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    // 如果有默认值，则使用默认值
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    // 否则无法解析依赖
                    throw new Exception("Unable to resolve dependency '{$parameter->getName()}'.");
                }
            } else {
                // 递归调用 make 方法获取依赖的实例
                $dependencies[] = $this->make($dependency->name);
            }
        }

        return $dependencies;
    }

    /**
     * 验证最后一次绑定的接口或抽象类是否为指定的接口或抽象类
     *
     * @param string $abstract 要验证的接口或抽象类名
     * @return $this 当前容器实例
     * @throws Exception 当验证失败时抛出异常
     */
    public function for(string $abstract): self
    {
        if ($this->lastBound !== $abstract) {
            throw new Exception("The last bound service is not '$abstract'.");
        }

        return $this;
    }
}

/**
<?php

use Imccc\Snail\Core\Container;

// 创建容器实例
$container = new Container();

// 示例 1: 绑定接口到具体实现类，并获取实例
$container->bind('SomeInterface', 'SomeImplementation');
$instance = $container->make('SomeInterface');

// 示例 2: 绑定为单例并获取共享实例
$container->bind('AnotherInterface', 'AnotherImplementation', true);
$sharedInstance = $container->make('AnotherInterface');

// 示例 3: 链式调用
$container->bind('ThirdInterface')->for('ThirdInterface')->bind('ThirdImplementation');
$thirdInstance = $container->make('ThirdInterface');

// 示例 4: 验证链式调用的调用顺序
try {
$container->bind('FourthInterface')->for('FifthInterface');
} catch (Exception $e) {
echo $e->getMessage() . PHP_EOL; // 输出: The last bound service is not 'FifthInterface'.
}
 */
