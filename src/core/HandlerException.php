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
    protected static $errorCount = 0;

    public static function handleException($exceptionOrErrorCode): void
    {
        self::$errorCount++;

        if ($exceptionOrErrorCode instanceof \Throwable) {
            $exception = $exceptionOrErrorCode;
        } else {
            $exception = new \ErrorException("Error: " . $exceptionOrErrorCode);
        }

        self::showError($exception);
        self::logError($exception);
    }

    public static function logError(\Throwable $exception): void
    {
        $log = sprintf(
            "[%s] (%s) [%s] [%d] [%s]",
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        error_log($log);
    }

    public static function showError(\Throwable $exception): void
    {
        echo '<div style="color: black; border: 1px dashed red; margin: 30px;">';
        echo '<h3 style="color: red; background-color: #eee; margin:0;padding: 10px;"> Snail Debug <small> - ' . $_SERVER['HTTP_HOST'] . '</small><span style="float:right;">#' . self::$errorCount . '</span></h3>';
        echo '<div style="padding: 10px;">';

        if (DEBUG ?? false) {
            self::showDetailedError($exception);
        } else {
            echo '<h1>Oops, something went wrong!</h1>';
            echo '<p>Please contact the administrator for assistance.</p>';
        }
        echo '</div></div>';
    }

    protected static function showDetailedError(\Throwable $exception): void
    {
        $errorCode = self::extractErrorCode($exception);
        $errorTypeMap = [
            '0' => 'EXCEPTION', '1' => 'ERROR', '2' => 'WARNING', '4' => 'PARSE',
            '8' => 'NOTICE', '16' => 'CORE_ERROR', '32' => 'CORE_WARNING', '64' => 'COMPILE_ERROR',
            '128' => 'COMPILE_WARNING', '256' => 'USER_ERROR', '512' => 'USER_WARNING',
            '1024' => 'USER_NOTICE', '2048' => 'STRICT', '4096' => 'RECOVERABLE_ERROR',
            '8192' => 'DEPRECATED', '16384' => 'USER_DEPRECATED',
        ];

        echo '<p>Error Type: ' . $errorTypeMap[$errorCode] . '</p>';
        echo '<p>Stack Trace:</p>';
        echo '<pre style="color:blue">' . self::formatStackTrace($exception->getTrace()) . '</pre>';
        echo '<p>Original Stack Trace:</p>';
        echo '<p style="color:gray">' . $exception->getTraceAsString() . '</p>';
    }

    protected static function extractErrorCode(\Throwable $exception): int
    {
        $parts = explode(":", $exception->getMessage());
        return count($parts) > 1 ? (int) trim($parts[1]) : 0;
    }

    /**
     * 格式化堆栈信息
     * @param array $trace
     * @return string
     */
    private static function formatStackTrace(array $trace): string
    {
        $formattedTrace = '';
        foreach ($trace as $index => $item) {
            $file = $item['file'] ?? '';
            $line = $item['line'] ?? '';
            $class = $item['class'] ?? '';
            $function = $item['function'] ?? '';

            $formattedTrace .= "第{$index}层调用：\n";
            $formattedTrace .= "文件：{$file} 行：{$line}\n";
            $formattedTrace .= "类名：{$class} 函数：{$function}\n";

            if (isset($item['args']) && is_array($item['args']) && !empty($item['args'])) {
                // 过滤掉非字符串参数
                $args = array_filter($item['args'], function ($arg) {
                    return is_string($arg);
                });
                // 如果还有参数，则将参数数组转换为字符串
                if (!empty($args)) {
                    $formattedArgs = implode(', ', $args);
                    $formattedTrace .= "<span style='color: red'>错误： 参数：{$formattedArgs}</span>\n";
                    // 如果文件和行号不为空，则显示源码
                    if (!empty($file) && !empty($line)) {
                        $sourceCode = self::getSourceCodeLine($file, $line);
                        if ($sourceCode !== null) {
                            $formattedTrace .= "<span style='color: green'>源码：{$sourceCode}</span>\n";
                        }
                    }
                }
            }
            $formattedTrace .= "\n";
        }
        return $formattedTrace;
    }

    protected static function getSourceCodeLine(string $filePath, int $lineNumber): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $file = new SplFileObject($filePath);
        $file->seek($lineNumber - 1);

        return $file->valid() ? $file->current() : null;
    }

    public static function getErrorCount(): int
    {
        return self::$errorCount;
    }

    public static function incrementErrorCount(): void
    {
        self::$errorCount++;
    }
}
