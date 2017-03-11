<?php

namespace Nopolabs\Yabot\Bot;

interface MessageDispatcherInterface
{
    public function setPrefix($prefix);
    public function setMatchers(array $matchers);
    public function dispatch($plugin, MessageInterface $message);
}