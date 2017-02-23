<?php

namespace Nopolabs\Yabot;


use Slack\Channel;

class Channels
{
    private $channels = [];
    private $channelsById = [];
    private $channelsByName = [];

    /**
     * @param Channel[] $channels
     */
    public function update(array $channels)
    {
        $this->channels = $channels;
        foreach ($channels as $index => $channel) {
            $this->channelsById[$channel->getId()] = $index;
            $this->channelsByName[$channel->getName()] = $index;
        }
    }

    public function byId($id)
    {
        $index = $this->channelsById[$id];
        return $this->channels[$index];
    }

    public function byName($name)
    {
        $index = $this->channelsByName[$name];
        return $this->channels[$index];
    }
}