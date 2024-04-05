<?php
namespace Imccc\Snail\Services;

use Exception;
use Imccc\Snail\Core\Container;

class LoggerService
{
    private $logQueue = []; // 日志队列，用于批量处理
    private $logFilePath; // 日志文件路径
    private $config; // 日志配置
    private $container; // 容器

    public function __construct(Container $container)
    {
        $this->container = $container;
        // 解析配置服务并获取日志配置信息
        $this->config = $this->container->resolve('ConfigService')->get('logger');

        // 注册一个脚本结束时的回调，用于处理日志队列中剩余的日志
        register_shutdown_function([$this, 'flushLogs']);
    }

    /**
     * 根据配置记录日志
     */
    public function log($message, $prefix = 'def')
    {
        print_r($message);die;
        $pre = $this->config['logprefix'][$prefix] ?? '';
        switch ($this->config['log_type']) {
            case 'file':
                // 如果配置为使用文件记录日志且当前日志类型在配置中启用，则将日志加入队列
                if ($this->config['on'][$prefix] ?? false) {
                    $this->enqueueLog("[$pre] $message", $prefix);
                }
                break;
            case 'server':
                // 如果配置为直接写入服务器日志，则直接写入
                if ($this->config['on'][$prefix] ?? false) {
                    $this->logToServer("[$pre] $message");
                }
                break;
            case 'database':
                // 如果配置为记录到数据库且当前日志类型在配置中启用，则记录到数据库
                if ($this->config['on'][$prefix] ?? false) {
                    $this->logToDatabase("$message", $prefix);
                }
                break;
        }
    }

    /**
     * 解析日志文件名
     *
     * @param string $type 日志类型
     * @return string 日志文件名
     */
    private function resolveFilename($type)
    {
        $filenamePrefix = $this->config['logprefix'][$type] ?? '_DEF_';
        return $this->config['log_file_path'] . '/' . $filenamePrefix . date('YmdH') . '.log';
    }

    /**
     * 将日志消息加入队列
     */
    private function enqueueLog($message, $prefix)
    {
        $logEntry = [
            'time' => date('Y-m-d H:i:s'),
            'message' => $message,
            'filename' => $prefix,
        ];

        $this->logQueue[] = $logEntry;

        // 如果达到批量处理的大小，则立即处理
        if (count($this->logQueue) >= $this->config['batch_size']) {
            $this->flushLogs();
        }
    }

    /**
     * 立即将日志队列中的日志写入到文件中
     */
    public function flushLogs()
    {
        // 如果队列为空，则直接返回
        if (empty($this->logQueue)) {
            return;
        }

        // 对日志进行分组处理，按照文件名分组
        $logsByFile = [];
        foreach ($this->logQueue as $logEntry) {
            $filename = $this->resolveFilename($logEntry['filename']);
            $logsByFile[$filename][] = "[" . $logEntry['time'] . "] - " . $logEntry['message'];
        }

        // 分别写入对应的文件
        foreach ($logsByFile as $filename => $messages) {
            file_put_contents($filename, implode(PHP_EOL, $messages) . PHP_EOL, FILE_APPEND);
        }

        // 清空日志队列
        $this->logQueue = [];
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
     * @param string $type 日志类型
     * @param string $tableName 数据库表名
     */
    private function logToDatabase($message, $type = '_DEF_', $tableName = 'logs')
    {
        // 使用容器解析数据库服务
        $sqlService = $this->container->resolve('SqlService');

        // 准备插入语句
        $sql = "INSERT INTO {$tableName} (times, message, type) VALUES (:times, :message, :type)";

        // 准备参数数组
        $params = [
            ':times' => date('Y-m-d H:i:s'),
            ':message' => $message,
            ':type' => $type,
        ];

        // 绑定参数并执行插入操作
        try {
            $sqlService->execute($sql, $params);
        } catch (Exception $e) {
            // 记录到服务器日志
            error_log("_ERROR_ : Failed to log to database: " . $e->getMessage());
        }
    }

    /**
     * 销毁
     */
    public function __destruct()
    {

    }
}
