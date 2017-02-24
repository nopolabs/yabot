<?php

namespace Nopolabs\Yabot\Reservations;


use DateTime;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Nopolabs\Yabot\Plugins\StorageTrait;
use Nopolabs\Yabot\Storage\StorageInterface;
use Nopolabs\Yabot\Yabot;
use Slack\User;

class Resources
{
    use StorageTrait;

    protected $name;
    protected $resources;

    public function __construct(Yabot $bot, array $keys, $name = 'resources')
    {
        $this->setStorageKey($name);
        $this->setStorage($bot->getStorage());

        $resources = $this->load();
        $this->resources = [];
        foreach ($keys as $key) {
            $resource = isset($resources[$key]) ? $resources[$key] : [];
            $this->resources[$key] = $resource;
        }

        $this->save();
    }

    public function isResource($key)
    {
        return array_key_exists($key, $this->resources);
    }

    public function getResource($key)
    {
        return $this->isResource($key) ? $this->resources[$key] : null;
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

    public function isReserved($key)
    {
        return !empty($this->resources[$key]);
    }

    public function getStatus($key)
    {
        return $this->getStatusAsync($key)->wait();
    }

    public function getStatusAsync($key) : PromiseInterface
    {
        $status = json_encode([$key => $this->resources[$key]]);

        return new FulfilledPromise($status);
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
}