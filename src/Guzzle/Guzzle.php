<?php

namespace Nopolabs\Yabot\Guzzle;


use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use function GuzzleHttp\Promise\queue;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class Guzzle
{
    /** @var Client */
    private $client;

    /** @var LoopInterface */
    private $eventloop;

    /** @var CurlMultiHandler */
    private $handler;

    /** @var TimerInterface */
    public $timer;

    public function __construct(LoopInterface $eventLoop, array $config = [])
    {
        $this->handler = new CurlMultiHandler();
        $config['handler'] = HandlerStack::create($this->handler);
        $this->client = new Client($config);
        $this->eventloop = $eventLoop;
    }

    public function getAsync($uri) : PromiseInterface
    {
        $request = $this->client->getAsync($uri);

        $this->schedule();

        return $request;
    }

    public function post($uri, $options) : ResponseInterface
    {
        return $this->client->post($uri, $options);
    }

    /**
     * @see http://stephencoakley.com/2015/06/11/integrating-guzzle-6-asynchronous-requests-with-reactphp
     */
    private function schedule()
    {
        if ($this->timer === null) {
            $self =& $this;
            $this->timer = $this->eventloop->addPeriodicTimer(0, \Closure::bind(function () use (&$self) {
                // Do a smidgen of request processing
                $this->tick();
                // Stop the timer when there are no more requests
                if (empty($this->handles) && queue()->isEmpty()) {
                    $self->timer->cancel();
                    $self->timer = null;
                }
            }, $this->handler, $this->handler));
        }
    }

    private function naiveSchedule($request)
    {
        // Schedule the request to be resolved later
        $this->eventloop->futureTick(function () use ($request) {
            $request->wait();
        });
    }
}