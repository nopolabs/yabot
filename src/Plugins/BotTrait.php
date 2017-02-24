<?php

namespace Nopolabs\Yabot\Plugins;


use Nopolabs\Yabot\Yabot;
use Slack\ChannelInterface;

trait BotTrait
{
    /** @var Yabot */
    private $bot;

    protected function setBot(Yabot $bot)
    {
        $this->bot = $bot;
    }

    protected function getBot() : Yabot
    {
        return $this->bot;
    }

    protected function say($text, $channel)
    {
        if (!($channel instanceof ChannelInterface)) {
            $channel = $this->getBot()->getChannels()->byName($channel);
        }
        $this->getBot()->say($text, $channel);
    }
}