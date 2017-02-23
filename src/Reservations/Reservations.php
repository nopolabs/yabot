<?php

namespace Nopolabs\Yabot\Reservations;


use DateTime;
use Nopolabs\Yabot\Message;
use Nopolabs\Yabot\Plugins\ChannelPluginTrait;
use Nopolabs\Yabot\Plugins\PluginInterface;

class Reservations implements PluginInterface
{
    use ChannelPluginTrait;

    /** @var Resources */
    protected $resources;

    public function getDefaultConfig() : array
    {
        return [
            'resourceCapture' => "(?'resource'\\w+)",
            'resourceNamePlural' => 'resources',
            'resources' => ['dev1', 'dev2', 'dev3'],
            'channel' => 'general',
            'matchers' => [
                'reserveForever' => "/reserve #resourceCapture# forever\\b/",
                'reserveUntil' => "/reserve #resourceCapture# until (?'until'.+)/",
                'reserve' => "/reserve #resourceCapture#/",
                'release' => "/release #resourceCapture#/",
                'list' => "/list #resourceNamePlural#\\b/",

//                'releaseMine' => "/release mine\\b/",
//                'releaseAll' => "/release all\\b/",
//                'isResourceFree' => "/is #resourceCapture# free\\b/",
//                'listMine' => "/what #resourceNamePlural# are mine\\b/",
//                'listFree' => "/what #resourceNamePlural# are free\\b/",
            ],
        ];
    }

    public function prepare()
    {
        $this->resources = new Resources($this->getStorage(), $this->config['resources']);

        $resourceCapture = $this->config['resourceCapture'];
        $resourceNamePlural = $this->config['resourceNamePlural'];

        $this->matchers = [];
        $channel = $this->config['channel'];
        $matchers = $this->config['matchers'];
        $matchers = $this->addChannelToMatchers($channel, $matchers);
        foreach ($matchers as $method => $matcher) {
            $pattern = $matcher['pattern'];
            $pattern = str_replace('#resourceCapture#', $resourceCapture, $pattern);
            $pattern = str_replace('#resourceNamePlural#', $resourceNamePlural, $pattern);
            $pattern = str_replace(' ', "\\s+", $pattern);
            $matcher['pattern'] = $pattern;
            $this->matchers[$method] = $matcher;
        }
    }

    public function reserve(Message $msg, array $matches)
    {
        $key = $matches['resource'];

        $resource = $this->resources->getResource($key);

        if ($resource === null) {
            $msg->reply("$key not found.");

        } elseif (empty($resource)) {
            $this->resources->reserve($key, $msg->getUser(), new DateTime('+ 12 hours'));
            $msg->reply("Reserved $key for {$msg->getUser()->getUsername()}.");
        } elseif ($resource['user'] === $msg->getUsername()) {

        }

        if ($this->resources->isResource($key)) {
            if ($this->resources->isReserved($key)) {
                $msg->reply("$key is already reserved.");
            } else {
                $this->resources->reserve($key, $msg->getUser(), new DateTime('+ 12 hours'));
                $msg->reply("Reserved $key for {$msg->getUser()->getUsername()}.");
            }
            $msg->reply($this->resources->getStatus($key));
        }
        $msg->setHandled(true);
    }

    public function reserveForever(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        if ($this->resources->isResource($key)) {
            if ($this->resources->isReserved($key)) {
                $msg->reply("$key is already reserved.");
            } else {
                $this->resources->reserve($key, $msg->getUser());
                $msg->reply("Reserved $key for {$msg->getUser()->getUsername()}.");
            }
            $msg->reply($this->resources->getStatus($key));
        }
        $msg->setHandled(true);
    }

    public function reserveUntil(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        $until = $matches['until'];
        if ($this->resources->isResource($key)) {
            if ($this->resources->isReserved($key)) {
                $msg->reply("$key is already reserved.");
            } else {
                $this->resources->reserve($key, $msg->getUser(), new DateTime($until));
                $msg->reply("Reserved $key for {$msg->getUser()->getUsername()}.");
            }
            $msg->reply($this->resources->getStatus($key));
        }
        $msg->setHandled(true);
    }

    public function release(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        if ($this->resources->isReserved($key)) {
            $this->resources->release($key);
            $msg->reply("Released $key.");
        }
        $msg->reply($this->resources->getStatus($key));
        $msg->setHandled(true);
    }

    public function list(Message $msg, array $matches)
    {
        $list = [];
        foreach ($this->resources->getKeys() as $key) {
            $list[] = $this->resources->getStatus($key);
        }
        $msg->reply(join("\n", $list));
        $msg->setHandled(true);
    }
}