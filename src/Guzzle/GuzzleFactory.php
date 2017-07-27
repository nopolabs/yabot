<?php
namespace Nopolabs\Yabot\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Nopolabs\ReactAwareGuzzleClientFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;

class GuzzleFactory
{
    private $eventLoop;
    private $logger;

    public function __construct(LoopInterface $eventLoop, LoggerInterface $logger = null)
    {
        $this->eventLoop = $eventLoop;
        $this->logger = $logger ?? new NullLogger();
    }

    public function newClient(array $config) : Client
    {
        $clientFactory = new ReactAwareGuzzleClientFactory();
        $reactAwareCurlFactory = $clientFactory->createReactAwareCurlFactory($this->eventLoop, null, $this->logger);
        $handler = $reactAwareCurlFactory->getHandler();
        $handlerStack =  $this->createHandlerStack($handler);
        $config['handler'] = $handlerStack;

        return new Client($config);
    }

    /**
     * Like HandlerStack::create() except no http_errors
     * @see http://docs.guzzlephp.org/en/stable/request-options.html#http-errors
     *
     * @param CurlMultiHandler $handler
     * @return HandlerStack
     */
    public function createHandlerStack(CurlMultiHandler $handler) : HandlerStack
    {
        $stack = new HandlerStack($handler);
        $stack->push(Middleware::redirect(), 'allow_redirects');
        $stack->push(Middleware::cookies(), 'cookies');
        $stack->push(Middleware::prepareBody(), 'prepare_body');

        return $stack;
    }
}