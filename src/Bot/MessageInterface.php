<?php

namespace Nopolabs\Yabot\Bot;

use Slack\Channel;
use Slack\User;

interface MessageInterface
{
    public function getText();

    public function setPluginText(string $text);

    public function getChannel();

    public function getUser();

    public function getThreadTs();

    public function isSelf() : bool;

    public function isBot() : bool;

    public function hasAttachments();

    public function getAttachments();

    public function reply(string $text, array $additionalParameters = []);

    public function thread(string $text, array $additionalParameters = []);

    public function isHandled() : bool;

    public function setHandled(bool $handled);

    public function getUsername();

    public function getChannelName();

    public function matchesPrefix(string $prefix) : array;

    public function matchesIsBot($isBot) : bool;

    public function matchesChannel($name) : bool;

    public function matchesUser($name) : bool;

    public function matchPatterns(array $patterns) : array;
}