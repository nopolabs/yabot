<?php

namespace Nopolabs\Yabot;


class Hey extends BasePlugin
{
    public function hey(Bot $bot, Message $msg, array $matches)
    {
        $bot->reply($msg, 'hey');
    }
}