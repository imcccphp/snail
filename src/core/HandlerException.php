<?php
/**
 * @desc    : 异常处理类
 * @author  : sam
 * @email   : sam@imccc.cc
 * @date    : 2024/2/26 19:27
 * @version : 1.0.0
 * @license : MIT
 */

declare (strict_types = 1);

namespace Imccc\Snail\Core;

use SplFileObject;

class HandlerException
{
    // 添加一个静态属性来跟踪错误数量
    protected static $errorCount = 0;
    /**
     * 处理异常
     * @param \Throwable $exception
     */
    public static function handleException($exceptionOrErrorCode): void
    {
        // 增加错误计数
        self::$errorCount++;

        if ($exceptionOrErrorCode instanceof \Throwable) {
            // 如果是异常，直接处理
            $exception = $exceptionOrErrorCode;
        } else {
            // 如果是错误，将其转换为异常
            $exception = new \ErrorException("Error: " . $exceptionOrErrorCode);
        }

        // 显示异常信息
        self::showError($exception);

        // 记录异常日志
        self::logError($exception);
    }

    /**
     * 记录异常日志
     * @param \Throwable $exception
     */
    public static function logError(\Throwable $exception): void
    {
        $message = $exception->getMessage();
        $code = $exception->getCode();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();

        $log = "[$message] ($code) [$file] [$line] [$trace]";

        // 实际的日志记录操作，这里使用 error_log 函数
        error_log($log);
    }

    /**
     * 显示异常信息
     * @param \Throwable $exception
     */
    public static function showError(\Throwable $exception): void
    {
        echo '<div style="color: black; border: 1px dashed red; margin: 30px;">';
        echo '<h3 style="color: red; background-color: #eee; margin:0;padding: 10px;"> Snail Debug <small> - ' . $_SERVER['HTTP_HOST'] . '</small><span style="float:right;">#' . self::$errorCount . '</span></h3>';
        echo '<div style="padding: 10px;">';

        // 在开发环境下显示详细的错误信息
        if (DEBUG) {
            // 从异常消息中尝试提取错误代码
            $parts = explode(":", $exception->getMessage());
            $ec = count($parts) > 1 ? trim($parts[1]) : null;

            // 错误类型映射数组
            $array_map = [
                '0' => 'EXCEPTION', '1' => 'ERROR', '2' => 'WARNING', '4' => 'PARSE',
                '8' => 'NOTICE', '16' => 'CORE_ERROR', '32' => 'CORE_WARNING', '64' => 'COMPILE_ERROR',
                '128' => 'COMPILE_WARNING', '256' => 'USER_ERROR', '512' => 'USER_WARNING',
                '1024' => 'USER_NOTICE', '2048' => 'STRICT', '4096' => 'RECOVERABLE_ERROR',
                '8192' => 'DEPRECATED', '16384' => 'USER_DEPRECATED',
            ];

            // echo '<p>' . $exception->getMessage() . '</p>';
            // echo '<p>文件: ' . $exception->getFile() . ' 行号: ' . $exception->getLine() . '</p>';
            // echo '<p>错误代码: ' . $exception->getCode() . '</p>';
            echo '<p>错误类型: ' . $array_map[$ec] . '</p>';

            echo '<p>堆栈调用:</p>';
            echo '<pre style="color:blue">' . self::formatStackTrace($exception->getTrace()) . '</pre>';

            echo '<p>原始堆栈:</p>';
            echo '<p style="color:gray">' . $exception->getTraceAsString() . '</p>';

        } else {
            // 在生产环境下显示友好的错误提示
            echo '<h1>哎呀，系统出问题了!</h1>';
            echo '<p>请联系管理员，我们会尽快处理。</p>';
        }
        echo '</div></div>';
    }

    /**
     * 格式化堆栈信息
     * @param $trace
     * @return string
     */
    private static function formatStackTrace($trace): string
    {
        $formattedTrace = '';
        // krsort($trace);
        foreach ($trace as $index => $item) {
            $file = $item['file'];
            $line = $item['line'];
            $formattedTrace .= "第{$index}层调用：\n";
            $formattedTrace .= "文件：{$file}";
            $formattedTrace .= " 行：{$line}\n";
            $formattedTrace .= "类名：{$item['class']}";
            $formattedTrace .= " 函数：{$item['function']}\n";
            if (count($item['args']) > 0) {
                $formattedTrace .= "<span style='color: red'>";
                $formattedTrace .= "错误：";
                $formattedTrace .= " 类型：{$item['args'][0]}";
                $formattedTrace .= " 信息：{$item['args'][1]}";
                $formattedTrace .= "</span>";
                if (isset($file) && isset($line)) {
                    $sourceCode = self::getSourceCodeLine($file, $line);
                    if (isset($sourceCode)) {
                        $formattedTrace .= "<pre style='color:green;margin: 0;'>源码：" . htmlspecialchars($sourceCode) . "</pre>\n";
                    }
                }
                // $formattedTrace .= "<pre style='color:green;margin: 0;'>源码：" . htmlspecialchars(self::getSourceCodeLine($file, $line)) . "</pre>\n";
            } else {
                $formattedTrace .= "\n";
            }

        }
        return $formattedTrace;
    }

    /**
     * 获取当前处理的错误数量。
     * @return int 错误数量。
     */
    public static function getErrorCount(): int
    {
        return self::$errorCount;
    }

    /**
     * 增加错误数量的方法，可用于外部显式增加错误计数（如果需要）。
     */
    public static function incrementErrorCount(): void
    {
        self::$errorCount++;
    }

    /**
     * 获取指定文件和行号的源代码行
     *
     * @param string $filePath 文件路径
     * @param int $lineNumber 行号
     * @return string|null 返回指定行的代码，如果找不到文件或行号超出范围，则返回 null
     */
    private static function getSourceCodeLine(string $filePath, int $lineNumber): ?string
    {
        // 检查文件是否存在
        if (!file_exists($filePath)) {
            return null;
        }

        $file = new SplFileObject($filePath);

        // 定位到指定行号（SplFileObject 的行号从 0 开始）
        $file->seek($lineNumber - 1);

        // 返回当前行的内容，如果行号超出文件长度，返回 null
        return $file->valid() ? $file->current() : null;
    }

}
