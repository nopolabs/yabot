<?php

namespace Nopolabs\Yabot\Bot;

interface PluginInterface
{
    public function help() : string;
    public function status() : string;
    public function init(array $config);
    public function onMessage(MessageInterface $message);
}