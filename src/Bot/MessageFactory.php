<?php

namespace Nopolabs\Yabot\Bot;

class MessageFactory
{
    public function create(SlackClient $slackClient, array $data) : MessageInterface
    {
        return new Message($slackClient, $data);
    }
}
