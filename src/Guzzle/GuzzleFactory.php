<?php
namespace Nopolabs\Yabot\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use React\EventLoop\LoopInterface;

class GuzzleFactory
{
    public static function newGuzzle(LoopInterface $eventLoop, array $config) : Guzzle
    {
        $handler = new CurlMultiHandler();

        $config['handler'] = HandlerStack::create($handler);

        $client = new Client($config);

        return new Guzzle($client, $handler, $eventLoop);
    }
}