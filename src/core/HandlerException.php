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

class HandlerException
{
    /**
     * 处理异常
     * @param \Throwable $exception
     */
    public static function handleException($exceptionOrErrorCode): void
    {
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
        echo '<h1 style="color: red; background-color: #eee; margin:0;padding: 10px;">' . SLIM_NAME . '  <small> Ver:' . SLIM_VERSION . '</small></h1>';
        echo '<div style="padding: 10px;">';

        // 在开发环境下显示详细的错误信息
        if (DEBUG) {
            $array_map = array('0' => 'EXCEPTION', '1' => 'ERROR', '2' => 'WARNING', '4' => 'PARSE', '8' => 'NOTICE', '16' => 'CORE_ERROR', '32' => 'CORE_WARNING', '64' => 'COMPILE_ERROR', '128' => 'COMPILE_WARNING', '256' => 'USER_ERROR', '512' => 'USER_WARNING', '1024' => 'USER_NOTICE', '2048' => 'STRICT', '4096' => 'RECOVERABLE_ERROR', '8192' => 'DEPRECATED', '16384' => 'USER_DEPRECATED');

            echo '<p>' . $exception->getMessage() . '</p>';
            echo '<p>文件: ' . $exception->getFile() . '</p>';
            echo '<p>行号: ' . $exception->getLine() . '</p>';
            echo '<p>错误代码: ' . $exception->getCode() . '</p>';
            echo '<p>错误类型:' . $array_map[$exception->getCode()] . '</p>';

            echo '<p>堆栈调用:</p>';
            echo '<pre style="color:blue">' . self::formatStackTrace($exception->getTrace()) . '</pre>';

            echo '<p>错误堆栈:</p>';
            echo '<p style="color:green">' . $exception->getTraceAsString() . '</p>';

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
    private static function formatStackTrace($trace)
    {
        $formattedTrace = '';
        foreach ($trace as $index => $item) {
            $formattedTrace .= "第{$index}层调用：\n";
            $formattedTrace .= "文件：{$item['file']}";
            $formattedTrace .= " 行：{$item['line']}\n";
            $formattedTrace .= "类名：{$item['class']}";
            $formattedTrace .= " 函数：{$item['function']}\n";
            if (count($item['args']) > 0) {
                $formattedTrace .= "<span style='color: red'>";
                $formattedTrace .= "参数：\n";
                $formattedTrace .= "错误：{$item['args'][0]}\n";
                $formattedTrace .= "错误：{$item['args'][1]}\n\n";
                $formattedTrace .= "</span>";
            } else {
                $formattedTrace .= "\n\n";
            }
        }
        return $formattedTrace;
    }
}
