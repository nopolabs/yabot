<?php

namespace Nopolabs\Yabot\Plugin;


use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Message\Message;
use Psr\Log\LoggerInterface;

class MethodMatcher
{
    use LogTrait;

    private $name;
    private $isBot;
    private $channels;
    private $users;
    private $patterns;
    private $method;

    public function __construct(
        string $name,
        $isBot,
        array $channels,
        array $users,
        array $patterns,
        string $method,
        LoggerInterface $logger)
    {
        $this->name = $name;
        $this->isBot = $isBot;
        $this->channels = $channels;
        $this->users = $users;
        $this->patterns = $patterns;
        $this->method = $method;
        $this->setLog($logger);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function matches(Message $message)
    {
        if ($message->isHandled()) {
            $this->debug('message already handled');
            return false;
        }

        if ($this->isBot !== null && $this->isBot !== $message->isBot()) {
            $this->debug('isBot match failed');
            return false;
        }

        if (!empty($this->channels) && !in_array($message->getChannelName(), $this->channels)) {
            $this->debug('channels match failed '.json_encode($this->channels));
            return false;
        }

        if (!empty($this->users) && !in_array($message->getUsername(), $this->users)) {
            $this->debug('users match failed '.json_encode($this->users));
            return false;
        }

        $matches = [];
        if (!empty($this->patterns)) {
            $text = $message->getPluginText();
            foreach ($this->patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    break;
                }
            }
            if (!$matches) {
                return false;
            }
        }

        return $matches;
    }

    private function debug($msg)
    {
        $this->debug("MethodMatcher {$this->name}: $msg");
    }
}