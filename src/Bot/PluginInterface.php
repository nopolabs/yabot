<?php

namespace Nopolabs\Yabot\Bot;

interface PluginInterface
{
    public function onMessage(MessageInterface $message);
}