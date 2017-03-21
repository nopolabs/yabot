<?php
namespace Nopolabs\Yabot\Http;

use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Http\Server;
use React\Socket\Server as SocketServer;
use React\Http\Request as HttpRequest;
use React\Http\Response as HttpResponse;

class HttpServer
{
    private $socket;
    private $http;
    private $logger;
    private $handlers;

    public function __construct($port, LoopInterface $loop, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->handlers = [];

        $this->socket = new SocketServer($port, $loop);
        $this->http = new Server($this->socket);

        $this->http->on('request', [$this, 'request']);
        $this->http->on('error', [$this, 'error']);
        $this->socket->on('error', [$this, 'error']);
    }

    public function addHandler(callable $handler)
    {
        $this->handlers[] = $handler;
    }

    public function request(HttpRequest $request, HttpResponse $response)
    {
        foreach ($this->handlers as $handler) {
            try {
                call_user_func_array($handler, [$request, $response]);
            } catch (Exception $e) {
                $this->error($e);
            }
        }
    }

    public function error(Exception $exception)
    {
        $this->logger->warning($exception->getMessage());
        $this->logger->warning($exception->getTraceAsString());
    }
}
