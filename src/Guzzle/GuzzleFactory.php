<?php

namespace Nopolabs\Yabot\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlMultiHandler;
use function GuzzleHttp\Promise\queue;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

/**
 * http://stephencoakley.com/2015/06/11/integrating-guzzle-6-asynchronous-requests-with-reactphp
 */
class GuzzleFactory
{
    /** @var array */
    private $config;

    /** @var LoopInterface */
    private $eventLoop;

    /** @var TimerInterface */
    private $timer;

    public function __construct(array $config = [], LoopInterface $eventLoop)
    {
        $this->config = $config;
        $this->eventLoop = $eventLoop;
    }

    public function createClient() : Client
    {
        $handler = new CurlMultiHandler();

        $this->config['handler'] = HandlerStack::create($handler);

        $this->timer = $this->eventLoop->addPeriodicTimer(0, function () use ($handler) {
            $handler->tick();
            if (empty($this->handles) && queue()->isEmpty()) {
                $this->timer->cancel();
            }
        });

        return new Client($this->config);
    }
}