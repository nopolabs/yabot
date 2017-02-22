<?php

namespace Nopolabs\Yabot\Plugins;


use DateTime;
use Nopolabs\Yabot\Message;
use Nopolabs\Yabot\Yabot;
use Slack\User;
use Slackyboy\Bot;
use Slackyboy\Plugins\PluginManager;

class Reservations extends ChannelPlugin
{
    protected $resources;

    public function __construct(Bot $bot, PluginManager $plugins)
    {
        parent::__construct($bot, $plugins);
    }

    public function enable()
    {
        $resources = $this->load('resources');

        $this->resources = [];

        foreach ($this->getPluginOptions()['resources'] as $key) {
            $this->resources[$key] = isset($resources[$key]) ? $resources[$key] : [];
        }

        $this->saveResources();

        parent::enable();
    }

    protected function getPluginOptions(array $default = null) : array
    {
        return parent::getPluginOptions([
            'channel' => 'general',
            'resourceCapture' => "(?'resource'\\w+)",
            'resourceNamePlural' => 'resources',
            'resources' => ['dev1', 'dev2', 'dev3'],
            'matchers' => [
                'reserve' => "/reserve #resourceCapture#/",
                'release' => "/release #resourceCapture#/",
                'list' => "/list #resourceNamePlural#\\b/",

//                'reserveForever' => "/reserve #resourceCapture# forever\\b/",
//                'reserveUntil' => "/reserve #resourceCapture# until (?'until'.+)/",
//                'releaseMine' => "/release mine\\b/",
//                'releaseAll' => "/release all\\b/",
//                'isResourceFree' => "/is #resourceCapture# free\\b/",
//                'listMine' => "/what #resourceNamePlural# are mine\\b/",
//                'listFree' => "/what #resourceNamePlural# are free\\b/",
            ],
        ]);
    }

    protected function prepareMatchers(array $options) : array
    {
        $matchers = [];

        $resourceCapture = $options['resourceCapture'];
        $resourceNamePlural = $options['resourceNamePlural'];

        foreach (parent::prepareMatchers($options) as $method => $matcher) {
            $matcher = str_replace('#resourceCapture#', $resourceCapture, $matcher);
            $matcher = str_replace('#resourceNamePlural#', $resourceNamePlural, $matcher);
            $matcher = str_replace(' ', "\\s+", $matcher);
            $matchers[$method] = $matcher;
        }

        return $matchers;
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

    public function reserve(Yabot $bot, Message $msg, array $matches)
    {
        $key = $matches['resource'];
        if ($this->isResource($key)) {
            if ($this->isReserved($key)) {
                $bot->reply($msg, "$key is alreday reserved.");
            } else {
                $this->makeReservation($key, $msg->getUser(), new DateTime('+ 12 hours'));
                $bot->reply($msg, "Reserved $key for {$msg->getUser()->getUsername()}.");
            }
            $bot->reply($msg, $this->getStatus($key));
        }
    }

    public function release(Yabot $bot, Message $msg, array $matches)
    {
        $key = $matches['resource'];
        if ($this->isReserved($key)) {
            $this->clearReservation($key);
            $bot->reply($msg, "Released $key.");
        }
        $bot->reply($msg, $this->getStatus($key));
    }

    public function list(Yabot $bot, Message $msg, array $matches)
    {
        $list = [];
        foreach (array_keys($this->resources) as $key) {
            $list[] = $this->getStatus($key);
        }
        $bot->reply($msg, join("\n", $list));
    }

    protected function makeReservation($key, User $user, DateTime $until)
    {
        $this->updateResource($key, [
            'user' => $user->getUsername(),
            'userId' => $user->getId(),
            'until' => $until->format('Y-m-d H:i:s'),
        ]);
    }

    protected function clearReservation($key)
    {
        $this->updateResource($key, []);
    }

    protected function updateResource($key, $data)
    {
        $this->resources[$key] = $data;
        $this->saveResources();
    }

    protected function saveResources()
    {
        $this->save('resources', $this->resources);
    }
}