<?php

namespace Nopolabs\Yabot\Plugins;


use Nopolabs\Yabot\Bot;
use Nopolabs\Yabot\Message;

class Hey extends BasePlugin
{
    protected function getConfig(array $default = null) : array
    {
        return parent::getConfig([
            'hey' => [
                'pattern' => "/\\b(?'hey'hey)\\b/",
                'channel' => 'general',
                'user' => 'dan',
                'method' => 'hey',
            ],
        ]);
    }

    public function hey(Bot $bot, Message $msg, array $matches)
    {
        $bot->reply($msg, 'hey');
    }
}