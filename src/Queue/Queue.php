<?php

namespace Nopolabs\Yabot\Queue;


use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Helpers\StorageTrait;
use Nopolabs\Yabot\Storage\StorageInterface;
use Nopolabs\Yabot\Yabot;

class Queue
{
    use StorageTrait;

    protected $queue;

    public function __construct(StorageInterface $storage, array $config)
    {
        $this->setStorageKey($config['storageName']);
        $this->setStorage($storage);

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
            if ($el['item'] !== $element['item']) {
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

    public function buildElement(MessageInterface $msg, array $matches)
    {
        return [
            'user' => $msg->getUsername(),
            'item' => $matches['item'],
        ];
    }

    public function getQueue() : array
    {
        return $this->queue;
    }

    public function getDetails() : array
    {
        $details = [];

        foreach ($this->getQueue() as $element) {
            $details[] = json_encode($element);
        }

        return $details;
    }
}