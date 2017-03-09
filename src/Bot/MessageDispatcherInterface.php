<?php

namespace Nopolabs\Yabot\Bot;

interface MessageDispatcherInterface
{
    public function setPrefix($prefix);

    public function dispatch($plugin, MessageInterface $message, array $matchers);
}