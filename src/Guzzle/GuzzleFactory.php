<?php
namespace Nopolabs\Yabot\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use React\EventLoop\LoopInterface;

class GuzzleFactory
{
    public static function newGuzzle(LoopInterface $eventLoop, array $config) : Guzzle
    {
        $handler = new CurlMultiHandler();

        $config['handler'] = self::createHandlerStack($handler);

        $client = new Client($config);

        return new Guzzle($client, $handler, $eventLoop);
    }

    /**
     * Like HandlerStack::create() except no http_errors
     * @return HandlerStack
     */
    public static function createHandlerStack(CurlMultiHandler $handler) : HandlerStack
    {
        $stack = new HandlerStack($handler);
        $stack->push(Middleware::redirect(), 'allow_redirects');
        $stack->push(Middleware::cookies(), 'cookies');
        $stack->push(Middleware::prepareBody(), 'prepare_body');

        return $stack;
    }
}