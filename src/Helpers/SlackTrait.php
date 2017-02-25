<?php

namespace Nopolabs\Yabot\Helpers;

use Nopolabs\Yabot\Bot\SlackClient;

trait SlackTrait
{
    /** @var SlackClient */
    private $slack;

    public function setSlack(SlackClient $slack)
    {
        $this->slack = $slack;
    }

    public function getSlack() : SlackClient
    {
        return $this->slack;
    }

    public function say($text, $channel)
    {
        $this->slack->say($text, $channel);
    }
}