<?php

namespace Nopolabs\Yabot\Plugins;


use Nopolabs\Yabot\Message;
use Nopolabs\Yabot\Yabot;

interface PluginInterface
{
    function onMessage(Message $message);
    function setBot(Yabot $bot);
    function setConfig(array $config);
    function prepare();
}
