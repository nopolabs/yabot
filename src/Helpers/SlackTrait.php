<?php

namespace Nopolabs\Yabot\Helpers;

use Nopolabs\Yabot\Slack\Client;

trait SlackTrait
{
    /** @var Client */
    private $slack;

    public function setSlack(Client $slack)
    {
        $this->slack = $slack;
    }

    public function getSlack() : Client
    {
        return $this->slack;
    }

    public function say($text, $channel, array $additionalParameters = [])
    {
        $this->getSlack()->say($text, $channel, $additionalParameters);
    }

    public function getUserById($userId)
    {
        return $this->getSlack()->getUserById($userId);
    }

    public function getChannelById($channelId)
    {
        return $this->getSlack()->getChannelById($channelId);
    }

    public function getAuthedUser()
    {
        return $this->getSlack()->getAuthedUser();
    }
}