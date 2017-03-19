<?php
namespace Nopolabs\Yabot\Http;

use Exception;
use HttpRequest;
use HttpResponse;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Http\Server;
use React\Socket\Server as SocketServer;

class HttpServer
{
    private $socket;
    private $http;
    private $logger;

    public function __construct($port, LoopInterface $loop, LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->socket = new SocketServer($port, $loop);
        $this->http = new Server($this->socket);

        $this->http->on('request', [$this, 'request']);
        $this->http->on('error', [$this, 'error']);
        $this->socket->on('error', [$this, 'error']);
    }

    public function request(HttpRequest $request, HttpResponse $response)
    {

    }

    public function error(Exception $exception)
    {
        $this->logger->warning($exception->getMessage());
        $this->logger->warning($exception->getTraceAsString());
    }
}
