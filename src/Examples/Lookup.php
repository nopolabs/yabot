<?php

namespace Nopolabs\Yabot\Examples;


use Nopolabs\Yabot\Plugins\ChannelPluginTrait;
use Nopolabs\Yabot\Plugins\PluginInterface;
use Nopolabs\Yabot\Yabot;
use Nopolabs\Yabot\Message;

class Lookup implements PluginInterface
{
    use ChannelPluginTrait;

    public function getDefaultConfig() : array
    {
        return [
            'channel' => 'general',
            'matchers' => [
                'lookupUser' => "/\\blookup <@(?'user'\\w+)>/",
                'lookupChannel' => "/\\blookup <#(?'channel'\\w+)\\|\\w+>/",
            ],
        ];
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