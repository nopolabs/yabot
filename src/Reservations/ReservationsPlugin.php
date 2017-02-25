<?php

namespace Nopolabs\Yabot\Reservations;


use DateTime;
use Nopolabs\Yabot\Message;
use Nopolabs\Yabot\Plugins\ChannelPluginTrait;
use Nopolabs\Yabot\Plugins\PluginInterface;

class ReservationsPlugin implements PluginInterface
{
    use ChannelPluginTrait;

    /** @var Resources */
    protected $resources;

    public function getDefaultConfig() : array
    {
        return [
            'resourcesClass' => 'Nopolabs\\Yabot\\Reservations\\Resources',
            'resourceCapture' => "(?'resource'\\w+)",
            'resourceNamePlural' => 'resources',
            'resourceKeys' => ['dev1', 'dev2', 'dev3'],
            'storageName' => 'resources',
            'channel' => 'general',
            'matchers' => [
                'reserveForever' => "/reserve #resourceCapture# forever\\b/",
                'reserveUntil' => "/reserve #resourceCapture# until (?'until'.+)/",
                'reserve' => "/reserve #resourceCapture#/",

                'release' => "/release #resourceCapture#/",
                'releaseMine' => "/release mine\\b/",
                'releaseAll' => "/release all\\b/",

                'list' => "/list #resourceNamePlural#\\b/",
                'listMine' => "/what #resourceNamePlural# are mine\\b/",
                'listFree' => "/what #resourceNamePlural# are free\\b/",

                'isFree' => "/is #resourceCapture# free\\b/",
            ],
        ];
    }

    public function prepare()
    {
        $resourceCapture = $this->config['resourceCapture'];
        $resourceNamePlural = $this->config['resourceNamePlural'];

        $channel = $this->config['channel'];
        $matchers = $this->config['matchers'];
        $matchers = $this->addChannelToMatchers($channel, $matchers);
        $matchers = $this->replaceInPatterns('#resourceCapture#', $resourceCapture, $matchers);
        $matchers = $this->replaceInPatterns('#resourceNamePlural#', $resourceNamePlural, $matchers);
        $matchers = $this->replaceInPatterns(' ', "\\s+", $matchers);
        $this->matchers = $matchers;

        $this->getLog()->info("PLUGIN: ".static::class);
        foreach ($matchers as $method => $matcher) {
            $this->getLog()->info("  MATCHER: {$method} => ".json_encode($matcher));
        }

        $resourcesClass = $this->config['resourcesClass'];
        $this->resources = new $resourcesClass($this->getBot(), $this->config);
    }

    public function reserve(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        $this->placeReservation($msg, $key, new DateTime('+ 12 hours'));
        $msg->setHandled(true);
    }

    public function reserveForever(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        $this->placeReservation($msg, $key);
        $msg->setHandled(true);
    }

    public function reserveUntil(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        $until = $matches['until'];
        $this->placeReservation($msg, $key, new DateTime($until));
        $msg->setHandled(true);
    }

    public function release(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        $this->releaseReservation($msg, $key);
        $msg->setHandled(true);
    }

    public function releaseMine(Message $msg, array $matches)
    {
        foreach ($this->resources->getAll() as $key => $resource) {
            if ($resource['user'] === $msg->getUsername()) {
                $this->releaseReservation($msg, $key);
            }
        }
        $msg->setHandled(true);
    }

    public function releaseAll(Message $msg, array $matches)
    {
        foreach ($this->resources->getKeys() as $key) {
            $this->releaseReservation($msg, $key);
        }
        $msg->setHandled(true);
    }

    public function list(Message $msg, array $matches)
    {
        $list = $this->resources->getAllStatuses();
        $msg->reply(join("\n", $list));
        $msg->setHandled(true);
    }

    public function listMine(Message $msg, array $matches)
    {
        $list = [];
        foreach ($this->resources->getAll() as $key => $resource) {
            if ($resource['user'] === $msg->getUsername()) {
                $list[] = $key;
            }
        }
        $msg->reply(join(',', $list));
        $msg->setHandled(true);
    }

    public function listFree(Message $msg, array $matches)
    {
        $list = [];
        foreach ($this->resources->getAll() as $key => $resource) {
            if (empty($resource)) {
                $list[] = $key;
            }
        }
        $msg->reply(join(',', $list));
        $msg->setHandled(true);
    }

    public function isFree(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        $resource = $this->resources->getResource($key);
        if ($resource === null) {
            $msg->reply("$key not found.");
        } else {
            if (empty($resource)) {
                $msg->reply("$key is free.");
            } else {
                $msg->reply("$key is reserved by {$resource['user']}");
            }
        }
        $msg->setHandled(true);
    }

    protected function placeReservation(Message $msg, $key, DateTime $until = null)
    {
        $resource = $this->resources->getResource($key);

        if ($resource === null) {
            $msg->reply("$key not found.");
        } else {
            if (empty($resource)) {
                $this->resources->reserve($key, $msg->getUser(), $until);
                $msg->reply("Reserved $key for {$msg->getUser()->getUsername()}.");
            } elseif ($resource['user'] === $msg->getUsername()) {
                $this->resources->reserve($key, $msg->getUser(), $until);
                $msg->reply("Updated $key for {$msg->getUser()->getUsername()}.");
            } else {
                $msg->reply("$key is reserved by {$resource['user']}");
            }
            $msg->reply($this->resources->getStatus($key));
        }
    }

    protected function releaseReservation(Message $msg, $key)
    {
        $resource = $this->resources->getResource($key);

        if ($resource === null) {
            $msg->reply("$key not found.");
        } else {
            if (empty($resource)) {
                $msg->reply("$key is not reserved.");
            } elseif ($resource['user'] === $msg->getUsername()) {
                $this->resources->release($key);
                $msg->reply("Released $key.");
            } else {
                $msg->reply("$key is reserved by {$resource['user']}");
            }
            $msg->reply($this->resources->getStatus($key));
        }
    }
}