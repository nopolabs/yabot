<?php

namespace Nopolabs\Yabot\Guzzle;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;

class Guzzle
{
    /** @var Client */
    private $client;

    /** @var LoopInterface */
    private $eventloop;

    public function __construct(Client $client, LoopInterface $eventLoop)
    {
        $this->client = $client;
        $this->eventloop = $eventLoop;
    }

    public function getAsync($uri) : PromiseInterface
    {
        $request = $this->client->getAsync($uri);

        // Schedule the request to be resolved later
        $this->eventloop->futureTick(function () use ($request) {
            $request->wait();
        });

        return $request;
    }

    public function post($uri, $options) : ResponseInterface
    {
        $this->client->post($uri, $options);
    }
}