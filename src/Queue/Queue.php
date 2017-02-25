<?php

namespace Nopolabs\Yabot\Queue;


use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Yabot;

class Queue
{
    use Nopolabs\Yabot\Helpers\StorageTrait;

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

    public function remove($element)
    {
        $queue = [];
        foreach ($this->queue as $el) {
            if ($el !== $element) {
                $queue[] = $el;
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

    public function buildElement(Message $msg, array $matches)
    {
        return $matches['element'];
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