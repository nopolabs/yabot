<?php

namespace Nopolabs\Yabot\Plugins;


use Nopolabs\Yabot\Storage\StorageInterface;

trait StorageTrait
{
    private $storage;
    private $storageKey;

    protected function setStorageKey($key)
    {
        $this->storageKey = str_replace('\\', '_', static::class).'.'.$key;
    }

    protected function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    protected function getStorage() : StorageInterface
    {
        return $this->storage;
    }

    protected function load()
    {
        return $this->getStorage()->get($this->storageKey);
    }

    protected function save()
    {
        $this->getStorage()->save($this->storageKey, $this->resources);
    }
}