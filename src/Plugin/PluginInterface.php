<?php

namespace Nopolabs\Yabot\Plugin;

use Nopolabs\Yabot\Message\Message;

interface PluginInterface
{
    public function help() : string;
    public function status() : string;
    public function init(string $pluginId, array $params);
    public function getPrefix() : string;
    public function handle(Message $message);
}