<?php

namespace Nopolabs\Yabot\Plugins;


use Nopolabs\Yabot\Yabot;
use Nopolabs\Yabot\Message;

class Hey extends BasePlugin
{
    public function hey(Yabot $bot, Message $msg, array $matches)
    {
        $bot->reply($msg, 'hey');
    }
}