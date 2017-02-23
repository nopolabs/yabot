<?php

namespace Nopolabs\Yabot\Examples;


use Nopolabs\Yabot\Plugins\PluginInterface;
use Nopolabs\Yabot\Plugins\PluginTrait;
use Nopolabs\Yabot\Message;

class Hey implements PluginInterface
{
    use PluginTrait;

    public function hey(Message $msg, array $matches)
    {
        $msg->reply('hey');
    }
}