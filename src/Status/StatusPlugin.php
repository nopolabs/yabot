<?php
namespace Nopolabs\Yabot\Status;

use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
use Psr\Log\LoggerInterface;

class StatusPlugin implements PluginInterface
{
    use PluginTrait;

    public function __construct(
        MessageDispatcher $dispatcher,
        LoggerInterface $logger)
    {
        $this->setDispatcher($dispatcher);
        $this->setLog($logger);

        $this->setMatchers(['status' => "/^status\\b/"]);
    }

    public function status(MessageInterface $msg, array $matches)
    {
        $msg->reply('running');
    }
}