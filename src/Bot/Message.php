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

    /** @var string */
    protected $formattedText;

    /** @var string */
    protected $pluginText;

    public function __construct(SlackClient $slack, array $data)
    {
        $this->data = $data;
        $this->setSlack($slack);
        $this->user = isset($data['user']) ? $slack->userById($data['user']) : null;
        $this->channel = $slack->channelById($data['channel']);
        $this->handled = false;
        $this->formattedText = $this->formatText($this->getText());
    }

    public function getText()
    {
        return $this->data['text'];
    }

    public function getFormattedText()
    {
        return $this->formattedText;
    }

    public function formatText(string $text) : string
    {
        $pattern = '/<([^>]*)>/';

        preg_match_all($pattern, $text, $matches);

        $split = preg_split($pattern, $text);

        $i = 0;
        $formatted = [];

        foreach ($matches[1] as $match) {
            $formatted[] = $split[$i++];
            if ($pipe = strrpos($match, '|')) {
                $fallback = substr($match, $pipe + 1);
                if ($match[0] === '@') {
                    $formatted[] = '@'.$fallback;
                } else if ($match[0] === '#') {
                    $formatted[] = '#'.$fallback;
                } else {
                    $formatted[] = $fallback;
                }
            } else {
                if ($match[0] === '@') {
                    $userId = substr($match, 1);
                    if ($user = $this->getSlack()->userById($userId)) {
                        $formatted[] = '@'.$user->getUsername();
                    } else {
                        $formatted[] = '@'.$userId;
                    }
                } elseif ($match[0] === '#') {
                    $channelId = substr($match, 1);
                    if ($channel = $this->getSlack()->channelById($channelId)) {
                        $formatted[] = '#'.$channel->getName();
                    } else {
                        $formatted[] = '#'.$channelId;
                    }
                } else {
                    $formatted[] = $match;
                }
            }
        }

        if ($i < count($split)) {
            $formatted[] = $split[$i];
        }

        return implode('', $formatted);
    }

    public function setPluginText(string $text)
    {
        $this->pluginText = $text;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getThreadTs()
    {
        return $this->data['thread_ts'] ?? $this->data['ts'];
    }

    public function isSelf() : bool
    {
        return $this->getSlack()->getAuthedUser() === $this->getUser();
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
        $user = $this->getUser();

        return $user ? $user->getUsername() : null;
    }

    public function getChannelName()
    {
        return $this->getChannel()->getName();
    }

    public function matchesPrefix(string $prefix) : array
    {
        $text = ltrim($this->formatText($this->getText()));

        if ($prefix === '') {
            return [$text, $text];
        }

        if ($prefix === Message::AUTHED_USER) {
            $user = $this->slack->getAuthedUser();
            $prefix = '@'.$user->getUsername();
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

    public function matchPatterns(array $patterns) : array
    {
        $matches = [];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->pluginText, $matches)) {
                break;
            }
        }

        return $matches;
    }
}