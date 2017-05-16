<?php

namespace Nopolabs\Yabot\Bot;


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

    /**
     * @param $id
     * @return Channel|null
     */
    public function byId($id)
    {
        if (isset($this->channelsById[$id])) {
            return $this->channels[$this->channelsById[$id]];
        } else {
            return null;
        }
    }

    /**
     * @param $name
     * @return Channel|null
     */
    public function byName($name)
    {
        if (isset($this->channelsByName[$name])) {
            return $this->channels[$this->channelsByName[$name]];
        } else {
            return null;
        }
    }
}
