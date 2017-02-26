<?php

namespace Nopolabs\Yabot\Bot;

use Slack\ChannelInterface;
use Slack\User;

class Message implements MessageInterface
{
    public $data;

    /** @var SlackClient */
    protected $slack;

    /** @var User */
    protected $user;

    /** @var ChannelInterface */
    protected $channel;

    /** @var bool */
    protected $handled;

    public function __construct(SlackClient $slack, array $data)
    {
        $this->data = $data;
        $this->slack = $slack;
        $this->user = $slack->userById($data['user']);
        $this->channel = $slack->channelById($data['channel']);
        $this->handled = false;
    }

    public function getText()
    {
        return $this->data['text'];
    }

    public function getChannel() : ChannelInterface
    {
        return $this->channel;
    }

    public function getUser() : User
    {
        return $this->user;
    }

    public function hasAttachments()
    {
        return isset($this->data['attachments']) && count($this->data['attachments']) > 0;
    }

    public function getAttachments()
    {
        return $this->hasAttachments() ? $this->data['attachments'] : [];
    }

    public function reply($text)
    {
        $this->slack->say($text, $this->getChannel());
    }

    public function isHandled() : bool
    {
        return $this->handled;
    }

    public function setHandled(bool $handled)
    {
        $this->handled = $handled;
    }

    public function getUsername()
    {
        return $this->getUser()->getUsername();
    }

    public function matchesChannel($name)
    {
        $channel = $this->slack->channelByName($name);
        return $this->channel === $channel;
    }

    public function matchesUser($name)
    {
        $names = is_array($name) ? $name : [$name];

        foreach ($names as $name) {
            $user = $this->slack->userByName($name);
            if ($this->user === $user) {
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