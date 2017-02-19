<?php

namespace Nopolabs\Yabot;


class Lookup extends ChannelPlugin
{
    public function lookupUser(Bot $bot, Message $msg, array $matches)
    {
        $bot->reply($msg, 'user: '.$matches['user']);
    }

    public function lookupChannel(Bot $bot, Message $msg, array $matches)
    {
        $bot->reply($msg, 'channel: '.$matches['channel']);
    }
}