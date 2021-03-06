<?php

namespace Nopolabs\Yabot\Plugin;


use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;

class MessageLogPlugin implements PluginInterface
{
    use PluginTrait;

    private $yabot;

    public function __construct(Yabot $yabot, LoggerInterface $logger = null)
    {
        $this->setLog($logger);
        $this->yabot = $yabot;

        $this->setConfig([
            'help' => "<prefix> start\n<prefix> stop\n",
            'prefix' => 'messagelog',
            'messageLogFile' => 'logs/message.log',
            'matchers' => [
                'start' => "/^start\\b/",
                'stop' => "/^stop\\b/",
            ],
        ]);
    }

    public function status(): string
    {
        if ($file = $this->yabot->getMessageLog()) {
            return "logging messages in $file";
        }

        return 'not logging messages';
    }

    public function start(Message $msg)
    {
        $this->yabot->setMessageLog($this->get('messageLogFile'));
        $msg->setHandled(true);
    }

    public function stop(Message $msg)
    {
        $this->yabot->setMessageLog(null);
        $msg->setHandled(true);
    }
}