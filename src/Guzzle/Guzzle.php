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
        $promise = new Promise();

        $request = $this->client->getAsync($uri);

        // Schedule the request to be resolved later
        $this->eventloop->futureTick(function () use ($request) {
            $request->wait();
        });

        // Use the response to resolve the promise
        $request->then(
            function (ResponseInterface $response) use ($promise) {
                $promise->resolve($response);
            },
            function (RequestException $e) use ($promise) {
                $promise->reject($e);
            }
        );

        return $promise;
    }

    public function post($uri, $options) : ResponseInterface
    {
        $this->client->post($uri, $options);
    }
}