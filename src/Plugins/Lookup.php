<?php

namespace Nopolabs\Yabot\Plugins;


use Nopolabs\Yabot\Bot;
use Nopolabs\Yabot\Message;

class Lookup extends ChannelPlugin
{
    protected function getConfig(array $default = null) : array
    {
        return parent::getConfig([
            'channel' => 'general',
            'matchers' => [
                'lookupUser' => "/\\blookup <@(?'user'\\w+)>/",
                'lookupChannel' => "/\\blookup <#(?'channel'\\w+)\\|\\w+>/",
            ],
        ]);
    }

    public function lookupUser(Bot $bot, Message $msg, array $matches)
    {
        $bot->reply($msg, 'user: '.$matches['user']);
    }

    public function lookupChannel(Bot $bot, Message $msg, array $matches)
    {
        $bot->reply($msg, 'channel: '.$matches['channel']);
    }
}