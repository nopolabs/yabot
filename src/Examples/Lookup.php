<?php

namespace Nopolabs\Yabot\Examples;


use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\PluginTrait;

class Lookup
{
    use PluginTrait;

    public function __construct(MessageDispatcher $dispatcher, array $config = [])
    {
        $default = [
            'channel' => 'general',
            'matchers' => [
                'lookupUser' => "/\\blookup <@(?'user'\\w+)>/",
                'lookupChannel' => "/\\blookup <#(?'channel'\\w+)\\|\\w+>/",
            ],
        ];

        $config = array_merge($default, $config);

        $channel = $config['channel'];
        $matchers = $config['matchers'];

        $matchers = $this->addToMatchers('channel', $channel, $matchers);

        $this->setMatchers($matchers);
        $this->setDispatcher($dispatcher);
    }

    public function lookupUser(Message $msg, array $matches)
    {
        $msg->reply('user: '.$matches['user']);
    }

    public function lookupChannel(Message $msg, array $matches)
    {
        $msg->reply('channel: '.$matches['channel']);
    }
}