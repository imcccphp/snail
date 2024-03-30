<?php

namespace Imccc\Snail\Services;

use Exception;
use Imccc\Snail\Core\Container;

class MailService
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $socket;
    private $connectionTimeout;
    private $responseTimeout;
    private $debug;
    private $log;
    private $logfile = '_MAIL_';
    protected $container;
    protected $logger;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $config = $this->container->resolve('ConfigService')->get('mail');
        $logger = $this->container->resolve('LoggerService');
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->connectionTimeout = $config['connectionTimeout'];
        $this->responseTimeout = $config['responseTimeout'];
        $this->debug = $config['debug'];
        $this->log = $config['log'];
    }

    private function connect()
    {
        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, $this->connectionTimeout);
        if ($this->log) {
            $this->logger->log('Connecting to SMTP host: ' . $this->host . ':' . $this->port, $logfile);
        }
        if (!$this->socket) {
            if ($this->debug) {
                $this->logger->log('Could not connect to SMTP host: ' . $errstr . ' (' . $errno . ')', $logfile);
            }
            throw new Exception("Could not connect to SMTP host: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, $this->responseTimeout);
        $this->readResponse();
    }

    private function authenticate()
    {
        $this->sendCommand("EHLO " . gethostname());
        $this->sendCommand("AUTH LOGIN");
        $this->sendCommand(base64_encode($this->username));
        $this->sendCommand(base64_encode($this->password));
    }

    private function sendCommand($command)
    {
        fputs($this->socket, $command . "\r\n");
        return $this->readResponse();
    }

    private function readResponse()
    {
        $response = "";
        while ($str = fgets($this->socket, 4096)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        return $response;
    }

    public function sendMail($from, $to, $subject, $body)
    {
        $this->connect();
        $this->authenticate();

        $this->sendCommand("MAIL FROM: <$from>");
        $this->sendCommand("RCPT TO: <$to>");
        $this->sendCommand("DATA");
        $this->sendCommand("Subject: $subject\r\nTo: <$to>\r\nFrom: <$from>\r\n\r\n$body\r\n.");
        $this->sendCommand("QUIT");
        fclose($this->socket);
    }
}
