<?php

namespace Nopolabs\Yabot\Guzzle;


use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use function GuzzleHttp\Promise\queue;
use Nopolabs\Yabot\Helpers\ConfigTrait;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class Guzzle
{
    use ConfigTrait;

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
        $this->setConfig($config);
    }

    public function getAsync(string $uri, array $options = []) : PromiseInterface
    {
        $request = $this->client->getAsync($uri, $options);

        $this->scheduleProcessing();

        return $request;
    }

    public function post(string $uri, array $options = []) : ResponseInterface
    {
        return $this->client->post($uri, $options);
    }

    /**
     * @see http://stephencoakley.com/2015/06/11/integrating-guzzle-6-asynchronous-requests-with-reactphp
     *
     * Jiggerery with Closure::bind to get access to CurlMultiHandler::handles private member.
     */
    private function scheduleProcessing()
    {
        if ($this->timer === null) {
            $self =& $this;
            $this->timer = $this->eventloop->addPeriodicTimer(0, \Closure::bind(function () use (&$self) {

                $this->tick();

                // Stop the timer when there are no more requests
                if (empty($this->handles) && queue()->isEmpty()) {
                    $self->timer->cancel();
                    $self->timer = null;
                }
            }, $this->handler, $this->handler));
        }
    }
}