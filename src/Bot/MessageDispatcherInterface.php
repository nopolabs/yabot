<?php

namespace Nopolabs\Yabot\Bot;

interface MessageDispatcherInterface
{
    public function setPrefix($prefix);
    public function getPrefix() : string;
    public function setMatchers(array $matchers);
    public function dispatch($plugin, MessageInterface $message, string $text);
}