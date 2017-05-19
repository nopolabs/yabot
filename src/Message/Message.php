<?php

namespace Nopolabs\Yabot\Message;

use Nopolabs\Yabot\Helpers\SlackTrait;
use Nopolabs\Yabot\Slack\Client;
use Slack\Channel;
use Slack\User;

class Message
{
    use SlackTrait;

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

    public function __construct(
        Client $slackClient,
        array $data,
        string $formattedText,
        User $user = null,
        Channel $channel)
    {
        $this->setSlack($slackClient);
        $this->data = $data;
        $this->user = $user;
        $this->channel = $channel;
        $this->handled = false;
        $this->formattedText = $formattedText;
    }

    public function getData() : array
    {
        return $this->data;
    }

    public function getText() : string
    {
        return $this->data['text'];
    }

    public function getFormattedText() : string
    {
        return $this->formattedText;
    }

    public function setPluginText(string $text)
    {
        $this->pluginText = $text;
    }

    public function getPluginText()
    {
        return $this->pluginText;
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
        return $this->user === $this->getAuthedUser();
    }

    public function isBot() : bool
    {
        if (isset($this->data['bot_id'])) {
            return true;
        }

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
}