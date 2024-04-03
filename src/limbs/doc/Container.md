# Container容器管理类

```
一个简单的 PHP 容器类，用于实现依赖注入和服务管理。该容器类提供了以下功能：

- 绑定接口或抽象类到具体实现类。
- 绑定参数到容器。
- 定义服务别名。
- 获取服务实例，支持别名。
- 标记服务。
- 创建具体实现类的新实例，自动解析构造函数的依赖关系。
- 验证最后一次绑定的接口或抽象类。
- 销毁绑定的实例或销毁所有绑定的实例。

这个容器类可以作为一个简单的依赖注入容器使用，用于管理应用程序中的各种服务和对象的实例化。通过绑定接口或抽象类到具体实现类，可以实现接口和实现类之间的解耦，并实现依赖倒置原则。
这个容器类实现了对构造函数参数的自动解析和注入。在 make 方法中，会解析具体实现类的构造函数参数的依赖关系，并递归地使用容器的 make 方法来获取依赖的实例。这样，当实例化一个具体实现类时，如果构造函数有参数，容器会自动注入所需的依赖实例，从而实现了自动注入的功能。
```

## 以下是该容器类提供的各个功能的用法示例，以及一个常规的完整用法示例：

### 1. 绑定服务
```php
// 使用 bind 方法将接口或抽象类绑定到具体实现类
$container->bind('LoggerService', 'FileLogger');
```

### 2. 绑定参数
```php
// 使用 bindParameter 方法将参数绑定到容器中
$container->bindParameter('debug', true);
```

### 3. 定义服务别名
```php
// 使用 alias 方法定义服务别名
$container->alias('Log', 'LoggerService');
```

### 4. 解析服务
```php
// 使用 resolve 方法获取服务实例，支持别名
$logService = $container->resolve('Log');
```

### 5. 标记服务
```php
// 使用 tag 方法给服务打上标记
$container->tag('LoggerService', 'logging');
```

### 6. 销毁实例
```php
// 使用 destroy 方法销毁指定服务的实例
$container->destroy('LoggerService');
```

### 7. 销毁所有实例
```php
// 使用 destroyAll 方法销毁所有服务的实例
$container->destroyAll();
```

### 完整用法示例
```php
use Imccc\Snail\Core\Container;

// 创建容器实例
$container = Container::getInstance();

// 绑定接口到具体实现类，并获取实例
$container->bind('SomeInterface', 'SomeImplementation');
$instance = $container->make('SomeInterface');

// 绑定为单例并获取共享实例
$container->bind('AnotherInterface', 'AnotherImplementation', true);
$sharedInstance = $container->make('AnotherInterface');

// 链式调用
$container->bind('ThirdInterface')->for('ThirdInterface')->bind('ThirdImplementation');
$thirdInstance = $container->make('ThirdInterface');

// 验证链式调用的调用顺序
try {
    $container->bind('FourthInterface')->for('FifthInterface');
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL; // 输出: The last bound service is not 'FifthInterface'.
}
```

这些示例演示了容器类的各种功能，包括绑定服务、绑定参数、定义服务别名、解析服务、标记服务、销毁实例和销毁所有实例。
