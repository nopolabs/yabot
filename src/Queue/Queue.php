<?php

namespace Nopolabs\Yabot\Queue;


use Nopolabs\Yabot\Plugins\StorageTrait;
use Nopolabs\Yabot\Yabot;

class Queue
{
    use StorageTrait;

    protected $queue;

    public function __construct(Yabot $bot, $name = 'queue')
    {
        $this->setStorageKey($name);
        $this->setStorage($bot->getStorage());

        $this->queue = $this->load();

        $this->save();
    }

    public function push($element)
    {
        array_push($this->queue, $element);
        $this->save();
    }

    public function next()
    {
        array_shift($this->queue);
        $this->save();
    }

    public function remove($el)
    {
        $queue = [];
        foreach ($this->queue as $element) {
            if ($el !== $element) {
                $queue[] = $element;
            }
        }
        $this->queue = $queue;
        $this->save();
    }

    public function clear()
    {
        $this->queue = [];
        $this->save();
    }

    public function getQueue() : array
    {
        return $this->queue;
    }

    public function getDetails() : array
    {
        return $this->getQueue();
    }
}