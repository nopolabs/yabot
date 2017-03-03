<?php

namespace Nopolabs\Yabot\Helpers;


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

    protected function addTimer($interval, $function)
    {
        $this->getLoop()->addTimer($interval, $this->callable($function));
    }

    protected function addPeriodicTimer($interval, $function)
    {
        $this->getLoop()->addPeriodicTimer($interval, $this->callable($function));
    }

    protected function cancelTimer(TimerInterface $timer)
    {
        $this->getLoop()->cancelTimer($timer);
    }

    private function callable($function) : callable
    {
        return function () use ($function) {
            call_user_func($function);
        };
    }
}