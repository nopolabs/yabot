<?php

namespace Nopolabs\Yabot\Reservations;


use DateTime;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Nopolabs\Yabot\Plugins\BotTrait;
use Nopolabs\Yabot\Plugins\LoopTrait;
use Nopolabs\Yabot\Plugins\StorageTrait;
use Nopolabs\Yabot\Yabot;
use Slack\User;

class Resources
{
    use StorageTrait;
    use LoopTrait;
    use BotTrait;

    protected $channel;
    protected $resources;

    public function __construct(Yabot $bot, array $keys, $channel, $name = 'resources')
    {
        $this->setStorage($bot->getStorage());
        $this->setStorageKey($name);

        $this->setLoop($bot->getLoop());
        $this->addPeriodicTimer(10, [$this, 'expireResources']);

        $this->setBot($bot);

        $resources = $this->load() ?: [];
        $this->resources = [];
        foreach ($keys as $key) {
            $resource = isset($resources[$key]) ? $resources[$key] : [];
            $this->resources[$key] = $resource;
        }

        $this->save($this->resources);
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
        $this->save($this->resources);
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

    public function isExpired($key)
    {
        if ($this->isReserved($key)) {
            $until = $this->getResource($key)['until'];
            if ($until === 'forever') {
                return false;
            } else {
                $expires = new DateTime($until);
                return $expires < new DateTime();
            }
        }
    }

    public function getStatus($key)
    {
        return $this->getStatusAsync($key)->wait();
    }

    public function getAllStatuses()
    {
        $statuses = [];

        foreach ($this->getKeys() as $key) {
            $statuses = $this->getStatusAsync($key);
        }

        return Promise\settle($statuses)->wait();
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

    public function expireResources()
    {
        foreach ($this->getKeys() as $key) {
            if ($this->isExpired($key)) {
                $this->release($key);
                $this->say("released $key", $this->channel);
            }
        }
    }
}