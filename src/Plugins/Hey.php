<?php

namespace Nopolabs\Yabot\Plugins;


use Nopolabs\Yabot\Yabot;
use Nopolabs\Yabot\Message;
use Psr\Log\LoggerInterface;

class Hey implements PluginInterface
{
    use PluginTrait;

    public function hey(Message $msg, array $matches)
    {
        $msg->reply('hey');
    }
}