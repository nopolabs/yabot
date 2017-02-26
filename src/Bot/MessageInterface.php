<?php

namespace Nopolabs\Yabot\Bot;

use Slack\ChannelInterface;
use Slack\User;

interface MessageInterface
{
    public function getText();

    public function getChannel() : ChannelInterface;

    public function getUser() : User;

    public function hasAttachments();

    public function getAttachments();

    public function reply($text);

    public function isHandled() : bool;

    public function setHandled(bool $handled);

    public function getUsername();

    public function matchesChannel($name);

    public function matchesUser($name);

    public function matchPattern($pattern);
}