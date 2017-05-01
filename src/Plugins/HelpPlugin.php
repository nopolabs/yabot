<?php
namespace Nopolabs\Yabot\Plugins;

use Nopolabs\Yabot\Bot\AbstractPlugin;
use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;

class HelpPlugin extends AbstractPlugin
{
    public function __construct(
        MessageDispatcher $dispatcher,
        LoggerInterface $logger,
        Yabot $yabot)
    {
        parent::__construct($dispatcher, $logger, $yabot);

        $this->setPrefix('@npbot');
        $this->setMatchers(['yabotHelp' => "/^help\\b/"]);
    }

    public function yabotHelp(MessageInterface $msg, array $matches)
    {
        $msg->reply($this->getYabot()->getHelp());
    }
}
