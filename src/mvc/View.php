<?php

namespace Imccc\Snail\Mvc;

class View
{
    protected $container;
    protected $config;
    protected $logger;
    protected $logprefix = ['view', 'error'];
    protected $templatePath;
    protected $templateTags;
    protected $_data = []; // 将 _datas 改为 _data
    protected $_cache;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->resolve('ConfigService');
        $this->logger = $container->resolve('LoggerService');

        $this->templatePath = $this->config->get('template.path');
        $this->templateTags = $this->config->get('template.tags');
    }

    /**
     * 分配数据给视图
     *
     * @param string|array $key 参数键名或参数数组
     * @param mixed $value 参数值（仅在第一个参数为键名时有效）
     */
    public function assign($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->_data[$k] = $v;
            }
        } else {
            $this->_data[$key] = $value;
        }
    }

    /**
     * 显示视图
     * @param string $tpl
     * @return string
     */
    public function display($tpl = null)
    {
        $this->logger->log('渲染视图：' . $tpl, $this->logprefix[0]);
        return $this->renderTemplate($tpl);
    }

    /**
     * 缓存视图
     * @param string $tpl
     */
    public function cache($tpl = null)
    {
        $this->logger->log('缓存视图：' . $tpl, $this->logprefix[0]);
        $this->_cache = $this->renderTemplate($tpl);
        return $this;
    }

    /**
     * 渲染模板
     * @param string $template
     * @param array $datas
     */
    public function render($template, $datas = [])
    {
        if (!empty($datas)) {
            $this->_data = array_merge($this->_data, $datas);
        }
        $this->logger->log('渲染模板：' . $template, $this->logprefix[0]);
        // 解析模板文件路径
        $templatePath = $this->resolveTemplatePath($template);

        // 渲染模板
        return $this->renderTemplate($templatePath, $datas);
    }

    /**
     * 解析模板文件路径，将相对路径转换为绝对路径
     *
     * @param string $template 相对路径的模板文件
     * @return string 绝对路径的模板文件
     */
    private function resolveTemplatePath($template)
    {
        // 获取当前模板文件所在目录
        $currentDirectory = dirname($_SERVER['SCRIPT_FILENAME']);

        // 构建绝对路径
        $absolutePath = $currentDirectory . DIRECTORY_SEPARATOR . $template;

        // 返回绝对路径
        return $absolutePath;
    }

    /**
     * 渲染模板
     * @param string $template
     */
    private function renderTemplate($template)
    {
        $templateFile = $this->templatePath . $template . '.php';
        if (file_exists($templateFile)) {
            // 读取模板文件内容
            $templateContent = file_get_contents($templateFile);

            // 解析模板标签
            $parsedTemplate = $this->parseTemplate($templateContent, $this->templateTags);

            // 使用一个关联数组来保存模板数据
            $templateData = $this->_data;

            // 开启输出缓冲
            ob_start();

            // 将模板内容作为 PHP 代码执行
            eval(' ?>' . $parsedTemplate . '<?php ');

            // 获取缓冲区内容并清空缓冲区
            $content = ob_get_clean();
            $this->logger->log('渲染模板：' . $template, $this->logprefix[0]);
            // 返回渲染后的内容
            return $content;
        } else {
            $this->logger->log('模板文件不存在：' . $template, $this->logprefix[1]);
            throw new HandlerException('模板文件不存在', 500);
        }
    }

    /**
     * 处理include模板
     *
     * @param string $template
     * @param array $datas
     */
    public function includeTemplate($template, $datas = [])
    {
        if (!empty($datas)) {
            $this->_data = array_merge($this->_data, $datas);
        }
        $this->logger->log('渲染模板：' . $template, $this->logprefix[0]);
        return $this->renderTemplate($template);
    }

    /**
     * 正则匹配并解析模板标签，包括 include 文件的相对路径
     *
     * @param string $template 模板内容
     * @param array $tags 模板标签映射数组
     * @return string 解析后的模板内容
     */
    public function parseTemplate($template, $tags)
    {
        foreach ($tags as $tag => $replacement) {
            if ($tag === 'include_file %%') {
                // 匹配 include_file %% 标签
                $pattern = '/' . str_replace('%%', '(.+)', preg_quote($tag, '/')) . '/';
                $template = preg_replace_callback($pattern, function ($matches) {
                    // 获取 include 文件的相对路径
                    $relativePath = trim($matches[1]);

                    // 获取当前模板文件所在目录
                    $currentDirectory = dirname($_SERVER['SCRIPT_FILENAME']);

                    // 解析相对路径为绝对路径
                    $absolutePath = $currentDirectory . DIRECTORY_SEPARATOR . $relativePath;

                    // 返回解析后的 include 标签
                    return '<?php include "' . $absolutePath . '";?>';
                }, $template);
            } else {
                // 对其他标签进行正常替换
                $pattern = '/' . str_replace('%%', '(.+)', preg_quote($tag, '/')) . '/';
                $template = preg_replace($pattern, $replacement, $template);
            }
        }
        $this->logger->log('解析模板标签：' . $template, $this->logprefix[0]);
        return $template;
    }

}
