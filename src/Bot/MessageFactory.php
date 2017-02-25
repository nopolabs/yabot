<?php

namespace Nopolabs\Yabot\Bot;


use Slack\Payload;

class MessageFactory
{
    /** @var SlackClient */
    private $slack;

    public function __construct(SlackClient $slack)
    {
        $this->slack = $slack;
    }

    public function create(Payload $payload) : Message
    {
        return new Message($this->slack, $payload->getData());
    }
}
