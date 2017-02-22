<?php

namespace Nopolabs\Yabot;


class Message extends \Slackyboy\Message
{
    public $data;
    protected $bot;
    protected $handled;

    public function __construct(Yabot $bot, array $data)
    {
        $this->bot = $bot;
        $this->data = $data;
        $this->handled = false;
        parent::__construct($bot->getSlackClient(), $data);
    }

    public function isHandled() : bool
    {
        return $this->handled;
    }

    public function setHandled(bool $handled) : bool
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

    public function getChannel()
    {
        return $this->getBot()->getChannelById($this->getChannelId());
    }

    public function getUserId()
    {
        return $this->data['user'];
    }

    public function getUser()
    {
        return $this->getBot()->getUserById($this->getUserId());
    }

    public function matchesChannel($channel)
    {
        $channelId = $this->getBot()->getChannelByName($channel)->getId();
        return $this->getChannelId() === $channelId;
    }

    public function matchesUser($user)
    {
        $users = is_array($user) ? $user : [$user];

        foreach ($users as $user) {
            $userId = $this->getBot()->getUserByName($user)->getId();
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