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
    protected $config;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $this->container->resolve('ConfigService')->get('mail');
        $this->logger = $this->container->resolve('LoggerService');
        $this->host = $this->config['host'];
        $this->port = $this->config['port'];
        $this->username = $this->config['username'];
        $this->password = $this->config['password'];
        $this->connectionTimeout = $this->config['connectionTimeout'];
        $this->responseTimeout = $this->config['responseTimeout'];
        $this->debug = $this->config['debug'];
        $this->log = $this->config['log'];
    }

    private function connect()
    {
        if ($this->config['tls']) {
            $this->socket = fsockopen("tls://" . $this->host, $this->port, $errno, $errstr, $this->connectionTimeout);
        } else {
            $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, $this->connectionTimeout);
        }
        if ($this->log) {
            $this->logger->log('Connecting to SMTP host: ' . $this->host . ':' . $this->port, $this->logfile);
        }
        if (!$this->socket) {
            if ($this->debug) {
                $this->logger->log('Could not connect to SMTP host: ' . $errstr . ' (' . $errno . ')', $this->logfile);
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
        if ($this->log) {
            $this->logger->log("Mail sent successfully.\r\n From: $from\r\n To: $to\r\n Subject: $subject\r\n Body: $body", $this->logfile);
        }

        fclose($this->socket);
    }
}
