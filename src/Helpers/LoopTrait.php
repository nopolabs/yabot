<?php

namespace Nopolabs\Yabot\Helpers;

use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use Throwable;

trait LoopTrait
{
    /** @var LoopInterface */
    private $loop;

    abstract protected function warning($message, array $context = array());

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
        return function() use ($function) {
            try {
                call_user_func($function);
            } catch (Throwable $throwable) {
                $errmsg = "Unhandled Exception in Timer callback\n"
                    .$throwable->getMessage()."\n"
                    .$throwable->getTraceAsString();
                $this->warning($errmsg);
            }
        };
    }
}