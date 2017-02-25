<?php

namespace Nopolabs\Yabot\Examples;


use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\PluginTrait;

class Hey
{
    use PluginTrait;

    public function __construct(MessageDispatcher $dispatcher, array $config = [])
    {
        $default =[
            'hey' => [
                'pattern' => "/\\b(?'hey'hey)\\b/",
                'channel' => 'general',
                'user' => 'dan',
                'method' => 'hey',
            ],
        ];

        $matchers = array_merge($default, $config);
        $matchers = $this->expandMatchers($matchers);

        $this->setMatchers($matchers);
        $this->setDispatcher($dispatcher);
    }

    public function hey(Message $msg, array $matches)
    {
        $msg->reply('hey');
    }
}