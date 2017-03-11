<?php

namespace Nopolabs\Yabot\Examples;

use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
use Psr\Log\LoggerInterface;

class Hey implements PluginInterface
{
    use PluginTrait;

    public function __construct(
        MessageDispatcher $dispatcher,
        LoggerInterface $logger,
        array $config = [])
    {
        $this->setDispatcher($dispatcher);
        $this->setLog($logger);

        $default =[
            'hey' => [
                'pattern' => "/^(?'hey'hey)\\b/",
                'channel' => 'general',
                'user' => 'dan',
                'method' => 'hey',
            ],
            'thread' => [
                'pattern' => "/^(?'thread'thread)\\b/",
            ],
        ];

        $matchers = array_merge($default, $config);

        $this->setMatchers($matchers);
    }

    public function hey(MessageInterface $msg, array $matches)
    {
        $msg->reply('hey');
    }

    public function thread(MessageInterface $msg, array $matches)
    {
        $attachments = ['attachments' => json_encode([["text" => "Choose a game to play"]])];
        $msg->thread('thread', $attachments);
    }
}