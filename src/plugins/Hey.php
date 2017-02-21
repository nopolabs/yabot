<?php

namespace Nopolabs\Yabot\Plugins;


use Nopolabs\Yabot\Bot;
use Nopolabs\Yabot\Message;

class Hey extends BasePlugin
{
    public function hey(Bot $bot, Message $msg, array $matches)
    {
        $bot->reply($msg, 'hey');
    }
}