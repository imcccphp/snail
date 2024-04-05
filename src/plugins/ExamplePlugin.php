<?php

namespace Imccc\Snail\Plugins;

use Imccc\Snail\Core\Container;
use Imccc\Snail\Core\PluginInterface;

class ExamplePlugin implements PluginInterface
{
    public function getName(): string
    {
        return 'ExamplePlugin';
    }

    public function onRegister(Container $container): void
    {
        echo "Plugin {$this->getName()} registered." . PHP_EOL;
    }

    public function onEnable(Container $container): void
    {
        echo "Plugin {$this->getName()} enabled." . PHP_EOL;
    }

    public function onDisable(Container $container): void
    {
        echo "Plugin {$this->getName()} disabled." . PHP_EOL;
    }

    public function onUninstall(Container $container): void
    {
        echo "Plugin {$this->getName()} uninstalled." . PHP_EOL;
    }

    public function getDependencies(): array
    {
        // 返回插件依赖的其他插件类名
        return [
            'Imccc\Snail\Plugins\AnotherPlugin',
        ];
    }
}
