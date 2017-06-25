<?php
namespace Nopolabs\Yabot\Guzzle;


use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Promise\PromiseInterface;
use function GuzzleHttp\Promise\queue;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class Guzzle
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getClient() : Client
    {
        return $this->client;
    }

    public function getAsync(string $uri, array $options = []) : PromiseInterface
    {
        return $this->client->getAsync($uri, $options);
    }

    public function post(string $uri, array $options = []) : ResponseInterface
    {
        return $this->client->post($uri, $options);
    }
}