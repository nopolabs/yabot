<?php

namespace Nopolabs\Yabot\Guzzle;


use Exception;
use GuzzleHttp\Handler\CurlFactory;
use GuzzleHttp\Handler\CurlFactoryInterface;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Handler\EasyHandle;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class ReactAwareCurlFactory implements CurlFactoryInterface
{
    private $eventLoop;
    private $logger;
    private $factory;
    private $count;

    /** @var CurlMultiHandler */
    private $handler;

    /** @var TimerInterface */
    private $timer;

    public function __construct(LoopInterface $eventLoop, LoggerInterface $logger = null)
    {
        $this->eventLoop = $eventLoop;
        $this->logger = $logger ?? new NullLogger();

        $this->factory = new CurlFactory(50);
        $this->count = 0;
    }

    public function setHandler(CurlMultiHandler $handler)
    {
        $this->handler = $handler;
    }

    public function tick()
    {
        try {
            $this->handler->tick();
        } catch (Exception $exception) {
            $this->logger->warning('TICK: '.$exception->getMessage());
        }

        if ($this->count === 0 && \GuzzleHttp\Promise\queue()->isEmpty()) {
            $this->stopTimer();
        }
    }

    public function create(RequestInterface $request, array $options)
    {
        $this->incrementCount();

        return $this->factory->create($request, $options);
    }

    public function release(EasyHandle $easy)
    {
        $this->factory->release($easy);

        $this->decrementCount();
    }

    private function incrementCount()
    {
        if ($this->count === 0) {
            $this->startTimer();
        }

        $this->count++;
    }

    private function decrementCount()
    {
        $this->count--;
    }

    private function startTimer()
    {
        if ($this->timer === null) {
            $this->timer = $this->eventLoop->addPeriodicTimer(0, [$this, 'tick']);

            $this->logger->debug('ReactAwareCurlFactory started periodic queue processing');
        }
    }

    private function stopTimer()
    {
        if ($this->timer !== null) {
            $this->timer->cancel();
            $this->timer = null;

            $this->logger->debug('ReactAwareCurlFactory stopped periodic queue processing');
        }
    }
}