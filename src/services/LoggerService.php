<?php
/**
 * 日志服务
 *
 * @package Imccc\Snail
 * @version 0.0.1
 * @author Imccc
 * @copyright Copyright (c) 2024 Imccc.
 * @license Apache-2.0
 * @link https://github.com/imcccphp/Snail
 * @lastModify 2024-03-30
 */
namespace Imccc\Snail\Services;

use Imccc\Snail\Core\Config;
use Imccc\Snail\Core\Container;

class LoggerService
{
    private $logFilePath; // 日志文件路径
    private $config; // 日志配置
    private $container; // 容器

    public function __construct(Container $container)
    {
        $this->container = $container;
        // 解析配置服务并获取日志配置信息
        $this->config = $this->container->resolve('ConfigService')->get('logger');
        // 日志文件路径
        $this->logFilePath = $this->config['log_file_path'];

        // 确保日志目录存在
        if (!is_dir($this->logFilePath)) {
            mkdir($this->logFilePath, 0777, true);
        }
    }

    /**
     * 根据配置记录日志
     */
    public function log($message, $filename = '_DEF_')
    {
        // 根据日志类型选择相应的记录方式
        switch ($this->config['log_type']) {
            case 'file':
                $this->logToFile($message, $filename);
                break;
            case 'server':
                $this->logToServer($message);
                break;
            case 'database':
                $this->logToDatabase($message, $filename);
                break;
        }
    }

    /**
     * 获取日志文件名
     *
     * @return string 日志文件路径
     */
    private function getLogFileName($filename = '_def_')
    {
        return $this->logFilePath . '/' . $filename . '_' . date('Y-m-d_H') . '.log';
    }

    /**
     * 记录日志到文件
     *
     * @param string $message 日志消息
     * @param string $filename 日志文件名
     */
    private function logToFile($message, $filename = '_def_')
    {
        // 添加时间戳到日志消息中
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
        // 每小时分割记录到文件
        file_put_contents($this->getLogFileName($filename), $logMessage, FILE_APPEND);
    }

    /**
     * 记录日志到服务器日志
     *
     * @param string $message 日志消息
     */
    private function logToServer($message)
    {
        // 添加时间戳到日志消息中
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
        // 记录到服务器日志
        error_log($logMessage);
    }

    /**
     * 记录日志到数据库
     *
     * @param string $message 日志消息
     * @param string $tableName 数据库表名
     */
    private function logToDatabase($message, $tableName = 'logs')
    {
        // 使用容器解析数据库服务并插入日志
        $this->container->resolve('SqlService')->insert($tableName, [
            'log_time' => date('Y-m-d H:i:s'),
            'message' => $message,
        ]);
    }
}
