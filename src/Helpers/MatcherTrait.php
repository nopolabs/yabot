<?php

namespace Nopolabs\Yabot\Helpers;


use Nopolabs\Yabot\Message\Message;

trait MatcherTrait
{
    private $name;
    private $isBot;
    private $channels;
    private $users;
    private $patterns;

    abstract protected function debug($message, array $context = array());

    public function matches(Message $message)
    {
        if (!$this->matchesIsHandled($message)) {
            return false;
        }

        if (!$this->matchesIsBot($message)) {
            return false;
        }

        if (!$this->matchesChannels($message)) {
            return false;
        }

        if (!$this->matchesUsers($message)) {
            return false;
        }

        if (empty($this->patterns)) {
            return true;
        }

        return $this->matchPatterns($message);
    }

    protected function matchesIsHandled(Message $message) : bool
    {
        if ($message->isHandled()) {
            $this->debug($this->name.': message already handled');
            return false;
        }

        return true;
    }

    protected function matchesIsBot(Message $message) : bool
    {
        if ($this->isBot !== null && $this->isBot !== $message->isBot()) {
            $this->debug($this->name.': isBot match failed');
            return false;
        }

        return true;
    }

    protected function matchesChannels(Message $message) : bool
    {
        if (!empty($this->channels) && !in_array($message->getChannelName(), $this->channels)) {
            $this->debug($this->name.': channels match failed '.json_encode($this->channels));
            return false;
        }

        return true;
    }

    protected function matchesUsers(Message $message) : bool
    {
        if (!empty($this->users) && !in_array($message->getUsername(), $this->users)) {
            $this->debug($this->name.': users match failed '.json_encode($this->users));
            return false;
        }

        return true;
    }

    protected function matchPatterns(Message $message): array
    {
        $matches = [];
        $text = $message->getPluginText();
        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                break;
            }
        }

        return $matches;
    }

    private function setName($name)
    {
        $this->name = $name;
    }

    private function setIsBot($isBot)
    {
        $this->isBot = $isBot;
    }

    private function setChannels(array $channels)
    {
        $this->channels = $channels;
    }

    private function setUsers(array $users)
    {
        $this->users = $users;
    }

    private function setPatterns(array $patterns)
    {
        $this->patterns = $patterns;
    }
}