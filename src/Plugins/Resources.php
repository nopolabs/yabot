<?php

namespace Nopolabs\Yabot\Plugins;


use DateTime;
use Nopolabs\Yabot\Storage\StorageInterface;
use Slack\User;

class Resources
{
    protected $storage;
    protected $resources;

    public function __construct(StorageInterface $storage, array $options)
    {
        $this->storage = $storage;
        $this->resources = [];

        $resources = $this->load();

        foreach ($options as $key) {
            $resource = isset($resources[$key]) ? $resources[$key] : [];
            $this->resources[$key] = $resource;
        }

        $this->save();
    }

    public function setResource($key, $resource)
    {
        $this->resources[$key] = $resource;
        $this->save();
    }

    public function getAll() : array
    {
        return $this->resources;
    }

    public function getKeys() : array
    {
        return array_keys($this->resources);
    }

    public function isResource($key)
    {
        return array_key_exists($key, $this->resources);
    }

    public function isReserved($key)
    {
        return !empty($this->resources[$key]);
    }

    public function getStatus($key)
    {
        return json_encode([$key => $this->resources[$key]]);
    }

    public function reserve($key, User $user, DateTime $until = null)
    {
        $this->setResource($key, [
            'user' => $user->getUsername(),
            'userId' => $user->getId(),
            'until' => $until ? $until->format('Y-m-d H:i:s') : 'forever',
        ]);
    }

    public function release($key)
    {
        $this->setResource($key, []);
    }

    protected function load()
    {
        return $this->storage->get($this->storageKey());
    }

    protected function save()
    {
        $this->storage->save($this->storageKey(), $this->resources);
    }

    protected function storageKey()
    {
        return str_replace('\\', '_', static::class).'.resources';
    }
}