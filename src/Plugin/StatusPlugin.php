<?php
namespace Nopolabs\Yabot\Plugin;

use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;

class StatusPlugin implements PluginInterface
{
    use PluginTrait;

    private $yabot;

    public function __construct(Yabot $yabot, LoggerInterface $logger = null)
    {
        $this->setLog($logger);
        $this->yabot = $yabot;

        $this->setConfig([
            'help' => '<prefix> status',
            'prefix' => PluginManager::AUTHED_USER_PREFIX,
            'matchers' => [
                'yabotStatus' => "/^status\\b/",
                'yabotShutdown' => "/^shutdown\\b/",
                'yabotReconnect' => "/^reconnect\\b/",
            ],
        ]);
    }

    public function yabotStatus(Message $msg)
    {
        $msg->reply($this->yabot->getStatus());
        $msg->setHandled(true);
    }

    public function yabotShutdown(Message $msg)
    {
        $this->yabot->shutDown();
        $msg->setHandled(true);
    }

    public function yabotReconnect(Message $msg)
    {
        $this->yabot->reconnect();
        $msg->setHandled(true);
    }
}