<?php

namespace Nopolabs\Yabot\Plugins;


use Nopolabs\Yabot\Yabot;
use Nopolabs\Yabot\Message;

class Lookup extends ChannelPlugin
{
    protected function getPluginOptions(array $default = null) : array
    {
        return parent::getPluginOptions([
            'channel' => 'general',
            'matchers' => [
                'lookupUser' => "/\\blookup <@(?'user'\\w+)>/",
                'lookupChannel' => "/\\blookup <#(?'channel'\\w+)\\|\\w+>/",
            ],
        ]);
    }

    public function lookupUser(Yabot $bot, Message $msg, array $matches)
    {
        $bot->reply($msg, 'user: '.$matches['user']);
    }

    public function lookupChannel(Yabot $bot, Message $msg, array $matches)
    {
        $bot->reply($msg, 'channel: '.$matches['channel']);
    }
}