<?php

namespace Nopolabs\Yabot\Examples;


use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\PluginTrait;
use Psr\Log\LoggerInterface;

class Lookup
{
    use PluginTrait;

    public function __construct(
        MessageDispatcher $dispatcher,
        LoggerInterface $logger,
        array $config = [])
    {
        $this->setDispatcher($dispatcher);
        $this->setLog($logger);

        $default = [
            'channel' => 'general',
            'matchers' => [
                'lookupUser' => "/^lookup <@(?'user'\\w+)>/",
                'lookupChannel' => "/^lookup <#(?'channel'\\w+)\\|\\w+>/",
            ],
        ];

        $config = array_merge($default, $config);

        $channel = $config['channel'];
        $matchers = $config['matchers'];

        $matchers = $this->addToMatchers('channel', $channel, $matchers);

        $this->setMatchers($matchers);
    }

    public function lookupUser(Message $msg, array $matches)
    {
        $msg->reply('user: '.$matches['user']);
    }

    public function lookupChannel(Message $msg, array $matches)
    {
        $msg->reply('channel: '.$matches['channel']);
    }
}