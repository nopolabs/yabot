<?php
namespace Nopolabs\Yabot\Status;

use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;

class StatusPlugin implements PluginInterface
{
    use PluginTrait;

    private $yabot;

    public function __construct(
        MessageDispatcher $dispatcher,
        LoggerInterface $logger,
        Yabot $yabot)
    {
        $this->setDispatcher($dispatcher);
        $this->setLog($logger);
        $this->yabot = $yabot;

        $this->setMatchers(['status' => "/^status\\b/"]);
    }

    public function status(MessageInterface $msg, array $matches)
    {
        $msg->reply($this->yabot->getStatus());
    }
}