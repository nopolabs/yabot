<?php

namespace Nopolabs\Yabot\Examples;

use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
use Psr\Log\LoggerInterface;

class Lookup implements PluginInterface
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

    public function lookupUser(MessageInterface $msg, array $matches)
    {
        $msg->reply('user: '.$matches['user']);
    }

    public function lookupChannel(MessageInterface $msg, array $matches)
    {
        $msg->reply('channel: '.$matches['channel']);
    }
}