<?php

namespace Imccc\Snail\Services;

use Imccc\Snail\Core\Container;

class SocketService
{
    protected $config;
    protected $logconf;
    protected $container;
    protected $logger;
    protected $logfile = '_SOCKET_';
    protected $socket;
    protected $address = '0.0.0.0';
    protected $port = 8080;
    protected $max_connections = 5;
    protected $max_buffer_size = 1024;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $this->container->resolve('ConfigService');
        $this->logger = $this->container->resolve('LoggerService');
        $this->logconf = $this->config->get('logger.on');
    }

    public function start()
    {
        // 创建一个 TCP/IP socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        // 绑定 IP 地址和端口
        $result = socket_bind($this->socket, $this->address, $this->port);

        if (!$result) {
            if ($this->logconf['socket']) {
                $this->logger->log("Socket 绑定失败：" . socket_strerror(socket_last_error()), $this->logfile);
            }
        }

        // 开始监听连接
        $result = socket_listen($this->socket, 5); // 最大连接数为 5

        if (!$result) {
            if ($this->logconf['socket']) {
                $this->logger->log("Socket 监听失败：" . socket_strerror(socket_last_error()), $this->logfile);
            }
        }

        echo "服务端启动，监听地址：{$this->address}，端口：{$this->port}" . PHP_EOL;

        // 循环接受客户端连接
        while (true) {
            // 接受客户端连接
            $clientSocket = socket_accept($this->socket);

            if (!$clientSocket) {
                if ($this->logconf['socket']) {
                    $this->logger->log("接受客户端连接失败：" . socket_strerror(socket_last_error()), $this->logfile);
                }
                echo "接受客户端连接失败：" . socket_strerror(socket_last_error()) . PHP_EOL;
                continue;
            }

            echo "客户端连接成功" . PHP_EOL;

            // 开启一个子进程来处理客户端请求
            $pid = pcntl_fork();
            if ($pid === -1) {
                // fork 失败
                echo "创建子进程失败" . PHP_EOL;
                if ($this->logconf['socket']) {
                    $this->logger->log("创建子进程失败：" . socket_strerror(socket_last_error()), $this->logfile);
                }
                continue;
            } elseif ($pid === 0) {
                // 子进程中处理客户端请求
                $this->handleClient($clientSocket);
                exit; // 子进程处理完毕后退出
            } else {
                // 父进程继续接受新的客户端连接
                continue;
            }
        }
    }

    protected function handleClient($clientSocket)
    {
        // 循环读取客户端发送的数据
        while (true) {
            // 读取客户端发送的数据
            $input = socket_read($clientSocket, 1024);

            if ($input === false) {
                if ($this->logconf['socket']) {
                    $this->logger->log("读取客户端数据失败：" . socket_strerror(socket_last_error()), $this->logfile);
                }
                echo "读取客户端数据失败：" . socket_strerror(socket_last_error()) . PHP_EOL;
                break;
            }

            if (trim($input) === 'quit') {
                // 客户端发送 quit 命令，则断开连接
                echo "客户端断开连接" . PHP_EOL;
                if ($this->logconf['socket']) {
                    $this->logger->log("客户端断开连接", $this->logfile);
                }
                socket_close($clientSocket);
                break;
            }

            echo "客户端发送的数据：$input" . PHP_EOL;

            // 向客户端发送响应数据
            $output = "服务器收到消息：" . $input . PHP_EOL;
            socket_write($clientSocket, $output, strlen($output));
        }
    }

    public function stop()
    {
        // 关闭 socket
        socket_close($this->socket);
    }
}
