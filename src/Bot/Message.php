<?php

namespace Nopolabs\Yabot\Bot;

use Nopolabs\Yabot\Helpers\SlackTrait;
use Slack\Channel;
use Slack\User;

class Message implements MessageInterface
{
    use SlackTrait;

    public $data;

    /** @var User */
    protected $user;

    /** @var Channel */
    protected $channel;

    /** @var bool */
    protected $handled;

    public function __construct(SlackClient $slack, array $data)
    {
        $this->data = $data;
        $this->setSlack($slack);
        $this->user = $slack->userById($data['user']);
        $this->channel = $slack->channelById($data['channel']);
        $this->handled = false;
    }

    public function getText()
    {
        return $this->data['text'];
    }

    public function getChannel() : Channel
    {
        return $this->channel;
    }

    public function getUser() : User
    {
        return $this->user;
    }

    public function getThreadTs()
    {
        if (isset($this->data['thread_ts'])) {
            return $this->data['thread_ts'];
        } else {
            return $this->data['ts'];
        }
    }

    public function isSelf() : bool
    {
        return $this->slack->getAuthedUser() === $this->getUser();
    }

    public function isBot() : bool
    {
        if (isset($this->user->data['is_bot'])) {
            return (bool) $this->user->data['is_bot'];
        } else {
            return false;
        }
    }

    public function hasAttachments()
    {
        return isset($this->data['attachments']) && count($this->data['attachments']) > 0;
    }

    public function getAttachments()
    {
        return $this->hasAttachments() ? $this->data['attachments'] : [];
    }

    public function reply($text, array $additionalParameters = [])
    {
        $this->say($text, $this->getChannel(), $additionalParameters);
    }

    public function thread($text, array $additionalParameters = [])
    {
        $additionalParameters['thread_ts'] = $this->getThreadTs();
        $this->reply($text, $additionalParameters);
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

    public function getChannelName()
    {
        return $this->getChannel()->getName();
    }

    public function matchesPrefix($prefix) : array
    {
        $text = ltrim($this->getText());

        if ($prefix === '') {
            return [$text, $text];
        }

        if (substr($prefix, 0, 1) === '@') {
            $user = $this->getSlack()->userByName(substr($prefix, 1));
            $prefix = "<@{$user->getId()}>";
        }

        preg_match("/^$prefix\s+(.*)/", $text, $matches);

        return $matches;
    }

    public function matchesIsBot($isBot) : bool
    {
        return $isBot === $this->isBot();
    }

    public function matchesChannel($name) : bool
    {
        $channelName = $this->getChannelName();
        if (is_array($name)) {
            return in_array($channelName, $name);
        } else {
            return $channelName === $name;
        }
    }

    public function matchesUser($name) : bool
    {
        $username = $this->getUsername();
        if (is_array($name)) {
            return in_array($username, $name);
        } else {
            return $username === $name;
        }
    }

    public function matchPattern($pattern, string $text) : array
    {
        $patterns = is_array($pattern) ? $pattern : [$pattern];
        $matches = [];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                break;
            }
        }
        return $matches;
    }
}