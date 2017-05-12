<?php
namespace Nopolabs\Yabot\Bot;

use Nopolabs\Yabot\Bot\AbstractPlugin;
use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;

class HelpPlugin extends AbstractPlugin
{
    public function __construct(LoggerInterface $logger, Yabot $yabot)
    {
        parent::__construct($logger, $yabot);

        $this->setConfig([
            'help' => '  help',
            'prefix' => Message::AUTHED_USER,
            'matchers' => ['yabotHelp' => "/^help\\b/"],
        ]);
    }

    public function yabotHelp(MessageInterface $msg, array $matches)
    {
        $msg->reply($this->getYabot()->getHelp());
        $msg->setHandled(true);
    }
}
