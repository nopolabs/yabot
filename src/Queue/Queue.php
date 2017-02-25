<?php

namespace Nopolabs\Yabot\Queue;


use Nopolabs\Yabot\Plugins\StorageTrait;
use Nopolabs\Yabot\Yabot;

class Queue
{
    use StorageTrait;

    protected $queue;

    public function __construct(Yabot $bot, array $config)
    {
        $this->setStorageKey($config['storageName']);
        $this->setStorage($bot->getStorage());

        $this->queue = $this->load() ?: [];

        $this->save($this->queue);
    }

    public function push($element)
    {
        array_push($this->queue, $element);
        $this->save($this->queue);
    }

    public function next()
    {
        array_shift($this->queue);
        $this->save($this->queue);
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
        $this->save($this->queue);
    }

    public function clear()
    {
        $this->queue = [];
        $this->save($this->queue);
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