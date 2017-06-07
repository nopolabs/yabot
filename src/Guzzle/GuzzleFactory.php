<?php
namespace Nopolabs\Yabot\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class GuzzleFactory
{
    public static function newClient(CurlMultiHandler $handler, array $config) : Client
    {
        $config['handler'] = self::createHandlerStack($handler);

        return new Client($config);
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