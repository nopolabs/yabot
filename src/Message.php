<?php

namespace Nopolabs\Yabot;


use Slack\ChannelInterface;
use Slack\User;

class Message extends \Slack\Message\Message
{
    public $data;
    protected $bot;
    protected $handled;

    public function __construct(Yabot $bot, array $data)
    {
        parent::__construct($bot->getClient(), $data);

        $this->bot = $bot;
        $this->data = $data;
        $this->handled = false;
    }

    public function reply($text)
    {
        $channel = $this->getBot()->getChannels()->byId($this->getChannelId());
        $this->getBot()->say($text, $channel);
    }

    public function isHandled() : bool
    {
        return $this->handled;
    }

    public function setHandled(bool $handled)
    {
        $this->handled = $handled;
    }

    public function getBot() : Yabot
    {
        return $this->bot;
    }

    public function getChannelId()
    {
        return $this->data['channel'];
    }

    public function getChannel() : ChannelInterface
    {
        return $this->getBot()->getChannels()->byId($this->getChannelId());
    }

    public function getUserId()
    {
        return $this->data['user'];
    }

    public function getUser() : User
    {
        return $this->getBot()->getUsers()->byId($this->getUserId());
    }

    public function getUsername()
    {
        return $this->getUser()->getUsername();
    }

    public function matchesChannel($channel)
    {
        $channelId = $this->getBot()->getChannels()->byName($channel)->getId();
        return $this->getChannelId() === $channelId;
    }

    public function matchesUser($user)
    {
        $users = is_array($user) ? $user : [$user];

        foreach ($users as $user) {
            $userId = $this->getBot()->getUsers()->byName($user)->getId();
            if ($this->getUserId() === $userId) {
                return true;
            }
        }

        return false;
    }

    public function matchPattern($pattern)
    {
        $patterns = is_array($pattern) ? $pattern : [$pattern];
        $text = $this->getText();
        $matches = [];
        $matched = false;
        foreach ($patterns as $pattern) {
            if ($matched = preg_match($pattern, $text, $matches)) {
                break;
            }
        }
        return $matched ? $matches : false;
    }
}