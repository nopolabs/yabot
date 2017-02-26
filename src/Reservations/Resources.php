<?php

namespace Nopolabs\Yabot\Reservations;


use DateTime;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Nopolabs\Yabot\Bot\SlackClient;
use Nopolabs\Yabot\Helpers\LoopTrait;
use Nopolabs\Yabot\Helpers\SlackTrait;
use Nopolabs\Yabot\Helpers\StorageTrait;
use Nopolabs\Yabot\Storage\StorageInterface;
use React\EventLoop\LoopInterface;
use Slack\User;

class Resources implements ResourcesInterface
{
    use StorageTrait;
    use LoopTrait;
    use SlackTrait;

    protected $channel;
    protected $resources;

    public function __construct(
        SlackClient $slack,
        StorageInterface $storage,
        LoopInterface $eventLoop,
        array $config)
    {
        $this->channel = $config['channel'];

        $this->setSlack($slack);

        $this->setStorage($storage);
        $this->setStorageKey($config['storageName']);

        $this->setLoop($eventLoop);
        $this->addPeriodicTimer(60, [$this, 'expireResources']);

        $resources = $this->load() ?: [];
        $this->resources = [];
        foreach ($config['keys'] as $key) {
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

    public function getStatus($key)
    {
        return $this->getStatusAsync($key)->wait();
    }

    public function getAllStatuses()
    {
        $requests = [];

        foreach ($this->getKeys() as $key) {
            $requests[] = $this->getStatusAsync($key);
        }

        $statuses = [];
        foreach (Promise\settle($requests)->wait() as $key => $result) {
            if ($result['state'] === PromiseInterface::FULFILLED) {
                $statuses[] = $result['value'];
            }
        }

        return $statuses;
    }

    public function getStatusAsync($key) : PromiseInterface
    {
        $status = json_encode([$key => $this->resources[$key]]);

        return new FulfilledPromise($status);
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

    protected function isExpired($key)
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

    protected function getReftags($envs)
    {
        $getTags = [];
        foreach ($envs as $env) {
            $uri = "https://$env.opensky.com/version/reftag";
            $getTags[$env] = $this->getAsync($uri);
        }

        $reftags = [];
        foreach (Promise\settle($getTags)->wait() as $env => $result) {
            if ($result['state'] === PromiseInterface::FULFILLED) {
                /** @var Response $rsp */
                $rsp = $result['value'];
                $reftags[$env] = trim($rsp->getBody());
            }
        }

        return $reftags;
    }
}