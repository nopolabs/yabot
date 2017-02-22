<?php

namespace Nopolabs\Yabot\Plugins;


use DateTime;
use Nopolabs\Yabot\Message;
use Nopolabs\Yabot\Yabot;
use Slackyboy\Bot;
use Slackyboy\Plugins\PluginManager;

class Reservations extends ChannelPlugin
{
    /** @var Resources */
    protected $resources;

    public function __construct(Bot $bot, PluginManager $plugins)
    {
        parent::__construct($bot, $plugins);
    }

    public function enable()
    {
        $options = $this->getPluginOptions()['resources'];

        $this->resources = new Resources($this->getBot()->getStorage(), $options);

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

    public function reserve(Yabot $bot, Message $msg, array $matches)
    {
        $key = $matches['resource'];
        if ($this->resources->isResource($key)) {
            if ($this->resources->isReserved($key)) {
                $bot->reply($msg, "$key is already reserved.");
            } else {
                $this->resources->reserve($key, $msg->getUser(), new DateTime('+ 12 hours'));
                $bot->reply($msg, "Reserved $key for {$msg->getUser()->getUsername()}.");
            }
            $bot->reply($msg, $this->resources->getStatus($key));
        }
        $msg->setHandled(true);
    }

    public function reserveForever(Yabot $bot, Message $msg, array $matches)
    {
        $key = $matches['resource'];
        if ($this->resources->isResource($key)) {
            if ($this->resources->isReserved($key)) {
                $bot->reply($msg, "$key is already reserved.");
            } else {
                $this->resources->reserve($key, $msg->getUser());
                $bot->reply($msg, "Reserved $key for {$msg->getUser()->getUsername()}.");
            }
            $bot->reply($msg, $this->resources->getStatus($key));
        }
        $msg->setHandled(true);
    }

    public function reserveUntil(Yabot $bot, Message $msg, array $matches)
    {
        $key = $matches['resource'];
        $until = $matches['until'];
        if ($this->resources->isResource($key)) {
            if ($this->resources->isReserved($key)) {
                $bot->reply($msg, "$key is already reserved.");
            } else {
                $this->resources->reserve($key, $msg->getUser(), new DateTime($until));
                $bot->reply($msg, "Reserved $key for {$msg->getUser()->getUsername()}.");
            }
            $bot->reply($msg, $this->resources->getStatus($key));
        }
        $msg->setHandled(true);
    }

    public function release(Yabot $bot, Message $msg, array $matches)
    {
        $key = $matches['resource'];
        if ($this->resources->isReserved($key)) {
            $this->resources->release($key);
            $bot->reply($msg, "Released $key.");
        }
        $bot->reply($msg, $this->resources->getStatus($key));
        $msg->setHandled(true);
    }

    public function list(Yabot $bot, Message $msg, array $matches)
    {
        $list = [];
        foreach ($this->resources->getKeys() as $key) {
            $list[] = $this->resources->getStatus($key);
        }
        $bot->reply($msg, join("\n", $list));
        $msg->setHandled(true);
    }
}