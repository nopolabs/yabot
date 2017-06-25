<?php
namespace Nopolabs\Yabot\Plugin;

use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;

class HelpPlugin implements PluginInterface
{
    use PluginTrait;

    private $yabot;

    public function __construct(Yabot $yabot, LoggerInterface $logger = null)
    {
        $this->setLog($logger);
        $this->yabot = $yabot;

        $this->setConfig([
            'help' => '<prefix> help',
            'prefix' => PluginManager::AUTHED_USER_PREFIX,
            'matchers' => ['yabotHelp' => "/^help\\b/"],
        ]);
    }

    public function yabotHelp(Message $msg)
    {
        $msg->reply($this->yabot->getHelp());
        $msg->setHandled(true);
    }
}
