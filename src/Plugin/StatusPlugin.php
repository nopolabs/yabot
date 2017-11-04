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
                'countUsers' => "/^count\\s+users\\b/",
                'countBots' => "/^count\\s+bots\\b/",
                'countChannels' => "/^count\\s+channels\\b/",
                'listUsers' => "/^list\\s+users\\b/",
                'listBots' => "/^list\\s+bots\\b/",
                'listChannels' => "/^list\\s+channels\\b/",
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

    public function countUsers(Message $msg)
    {
        $map = $this->yabot->getSlack()->getUsersMap();

        $msg->reply(count($map));
        $msg->setHandled(true);
    }

    public function countBots(Message $msg)
    {
        $map = $this->yabot->getSlack()->getBotsMap();

        $msg->reply(count($map));
        $msg->setHandled(true);
    }

    public function countChannels(Message $msg)
    {
        $map = $this->yabot->getSlack()->getChannelsMap();

        $msg->reply(count($map));
        $msg->setHandled(true);
    }

    public function listUsers(Message $msg)
    {
        $map = $this->yabot->getSlack()->getUsersMap();

        $list = json_encode($map);

        $msg->reply($list);
        $msg->setHandled(true);
    }

    public function listBots(Message $msg)
    {
        $map = $this->yabot->getSlack()->getBotsMap();

        $list = json_encode($map);

        $msg->reply($list);
        $msg->setHandled(true);
    }

    public function listChannels(Message $msg)
    {
        $map = $this->yabot->getSlack()->getChannelsMap();

        $list = json_encode($map);

        $msg->reply($list);
        $msg->setHandled(true);
    }
}