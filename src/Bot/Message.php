<?php

namespace Nopolabs\Yabot\Bot;

use Nopolabs\Yabot\Helpers\SlackTrait;
use Slack\Channel;
use Slack\User;

class Message implements MessageInterface
{
    use SlackTrait;

    const AUTHED_USER = 'AUTHED_USER';

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
        return $this->data['thread_ts'] ?? $this->data['ts'];
    }

    public function isSelf() : bool
    {
        return $this->slack->getAuthedUser() === $this->getUser();
    }

    public function isBot() : bool
    {
        if (isset($this->user->data['is_bot'])) {
            return (bool) $this->user->data['is_bot'];
        }

        return false;
    }

    public function hasAttachments()
    {
        return isset($this->data['attachments']) && count($this->data['attachments']) > 0;
    }

    public function getAttachments()
    {
        return $this->hasAttachments() ? $this->data['attachments'] : [];
    }

    public function reply(string $text, array $additionalParameters = [])
    {
        $this->say($text, $this->getChannel(), $additionalParameters);
    }

    public function thread(string $text, array $additionalParameters = [])
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

    public function matchesPrefix(string $prefix) : array
    {
        $text = ltrim($this->getText());

        if ($prefix === '') {
            return [$text, $text];
        }

        if ($prefix === Message::AUTHED_USER) {
            $user = $this->slack->getAuthedUser();
            $prefix = "<@{$user->getId()}>";
        } else if ($prefix[0] === '@') {
            $user = $this->getSlack()->userByName(substr($prefix, 1));
            $prefix = "<@{$user->getId()}>";
        }

        preg_match("/^$prefix\\s+(.*)/", $text, $matches);

        return $matches;
    }

    public function matchesIsBot($isBot) : bool
    {
        if ($isBot === null) {
            return true;
        }

        return $isBot === $this->isBot();
    }

    public function matchesChannel($name) : bool
    {
        if (!$name) {
            return true;
        }

        $channelName = $this->getChannelName();

        if (is_array($name)) {
            return in_array($channelName, $name);
        }

        return $channelName === $name;
    }

    public function matchesUser($name) : bool
    {
        if (!$name) {
            return true;
        }

        $username = $this->getUsername();

        if (is_array($name)) {
            return in_array($username, $name);
        }

        return $username === $name;
    }

    public function matchPatterns(array $patterns, string $text) : array
    {
        $matches = [];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                break;
            }
        }

        return $matches;
    }
}