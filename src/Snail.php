<?php

namespace Imccc\Snail;

class Snail
{
    const SNAIL = 'Snail';
    const SNAIL_VERSION = '0.0.1';

    public function __construct()
    {
        $this->run();
    }

    /**
     * 运行入口
     */
    public function run()
    {
        // 定义Composer自动加载文件的路径
        $composerAutoloadPath = __DIR__ . '/vendor/autoload.php';

        // 检查autoload.php文件是否存在
        if (file_exists($composerAutoloadPath)) {
            // 如果存在，引入autoload.php文件
            require_once $composerAutoloadPath;
        } else {
            // 如果不存在，执行没有Composer环境下的备选方案
            // 例如，可以手动引入项目需要的文件，或者显示错误消息等
            // 这里只是打印一个简单的错误消息作为示例
            echo "Composer autoload file not found. Please run 'composer install'.";
            exit; // 或者根据你的项目需求进行其他处理
        }

        // 之后的代码将会使用通过Composer自动加载的类库
        // 例如，使用通过Composer安装的库
        // $someLibrary = new SomeLibrary();

    }

}
