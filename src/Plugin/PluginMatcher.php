<?php

namespace Nopolabs\Yabot\Plugin;


use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Message\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class PluginMatcher
{
    use LogTrait;

    private $pluginId;
    private $isBot;
    private $channels;
    private $users;

    public function __construct(
        string $pluginId,
        bool $isBot = null,
        array $channels,
        array $users,
        LoggerInterface $logger)
    {
        $this->pluginId = $pluginId;
        $this->isBot = $isBot;
        $this->channels = $channels;
        $this->users = $users;
        $this->setLog($logger);
    }

    public function matches(Message $message) : bool
    {
        if ($message->isHandled()) {
            $this->debug('message already handled');
            return false;
        }

        if ($this->isBot !== null && $this->isBot !== $message->isBot()) {
            $this->debug('isBot match failed');
            return false;
        }

        if ($this->channels && !in_array($message->getChannelName(), $this->channels)) {
            $this->debug('channels match failed '.json_encode($this->channels));
            return false;
        }

        if ($this->users && !in_array($message->getUsername(), $this->users)) {
            $this->debug('users match failed '.json_encode($this->users));
            return false;
        }

        return true;
    }

    private function debug($msg)
    {
        $this->log(LogLevel::DEBUG, "PluginMatcher {$this->pluginId}: $msg");
    }
}
