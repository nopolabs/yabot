<?php

namespace Nopolabs\Yabot\Bot;

use Slack\Channel;
use Slack\User;

interface MessageInterface
{
    public function getText();

    public function getChannel() : Channel;

    public function getUser() : User;

    public function isBot() : bool;

    public function hasAttachments();

    public function getAttachments();

    public function reply($text);

    public function isHandled() : bool;

    public function setHandled(bool $handled);

    public function getUsername();

    public function matchesPrefix($prefix) : bool;

    public function matchesIsBot($isBot) : bool;

    public function matchesChannel($name) : bool;

    public function matchesUser($name) : bool;

    public function matchPattern($pattern);
}