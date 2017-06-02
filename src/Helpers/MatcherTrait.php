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
        if ($message->isHandled()) {
            $this->debug($this->name.': message already handled');
            return false;
        }

        if ($this->isBot !== null && $this->isBot !== $message->isBot()) {
            $this->debug($this->name.': isBot match failed');
            return false;
        }

        if (!empty($this->channels) && !in_array($message->getChannelName(), $this->channels)) {
            $this->debug($this->name.': channels match failed '.json_encode($this->channels));
            return false;
        }

        if (!empty($this->users) && !in_array($message->getUsername(), $this->users)) {
            $this->debug($this->name.': users match failed '.json_encode($this->users));
            return false;
        }

        if (empty($this->patterns)) {
            return true;
        }

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