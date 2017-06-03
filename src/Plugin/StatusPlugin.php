<?php
namespace Nopolabs\Yabot\Plugin;

use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;

class StatusPlugin implements PluginInterface
{
    use PluginTrait;

    private $yabot;

    public function __construct(LoggerInterface $logger, Yabot $yabot)
    {
        $this->setLog($logger);
        $this->yabot = $yabot;

        $this->setConfig([
            'help' => '<prefix> status',
            'prefix' => PluginManager::AUTHED_USER_PREFIX,
            'matchers' => ['yabotStatus' => "/^status\\b/"],
        ]);
    }

    public function yabotStatus(Message $msg, array $matches)
    {
        $msg->reply($this->yabot->getStatus());
        $msg->setHandled(true);
    }
}