<?php

namespace Nopolabs\Yabot\Plugins;


use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

trait LoopTrait
{
    /** @var LoopInterface */
    private $loop;

    protected function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    protected function getLoop() : LoopInterface
    {
        return $this->loop;
    }

    protected function addTimer($interval, callable $callback)
    {
        $this->getLoop()->addTimer($interval, $callback);
    }

    protected function addPeriodicTimer($interval, callable $callback)
    {
        $this->getLoop()->addPeriodicTimer($interval, $callback);
    }

    protected function cancelTimer(TimerInterface $timer)
    {
        $this->getLoop()->cancelTimer($timer);
    }
}