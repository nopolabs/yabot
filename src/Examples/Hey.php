<?php

namespace Nopolabs\Yabot\Examples;


use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\PluginTrait;
use Psr\Log\LoggerInterface;

class Hey
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
        ];

        $matchers = array_merge($default, $config);
        $matchers = $this->expandMatchers($matchers);

        $this->setMatchers($matchers);
    }

    public function hey(Message $msg, array $matches)
    {
        $msg->reply('hey');
    }
}