<?php

namespace Nopolabs\Yabot\Bot;

interface PluginInterface
{
    public function help() : string;
    public function status() : string;
    public function init(string $pluginId, array $params);
    public function getPrefix() : string;
    public function dispatch(MessageInterface $message, string $text);
}