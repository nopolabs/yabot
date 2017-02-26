<?php

namespace Nopolabs\Yabot\Bot;

interface MessageDispatcherInterface
{
    public function dispatch($plugin, MessageInterface $message, array $matchers);
}