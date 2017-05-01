<?php

namespace Nopolabs\Yabot;

use Evenement\EventEmitterTrait;
use Exception;
use Nopolabs\Yabot\Bot\MessageFactory;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\SlackClient;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Slack\Payload;

class Yabot
{
    use EventEmitterTrait;

    /** @var LoggerInterface */
    protected $logger;

    /** @var LoopInterface */
    protected $eventLoop;

    /** @var SlackClient */
    protected $slackClient;

    /** @var MessageFactory */
    protected $messageFactory;

    /** @var array */
    protected $plugins;

    /** @var array */
    protected $listeners;

    public function __construct(
        LoggerInterface $logger,
        LoopInterface $eventLoop,
        SlackClient $slackClient,
        MessageFactory $messageFactory
    ) {
        $this->logger = $logger;
        $this->eventLoop = $eventLoop;
        $this->slackClient = $slackClient;
        $this->messageFactory = $messageFactory;

        $this->plugins = [];
        $this->listeners = [];
    }

    public function run()
    {
        $this->slackClient->on('message', [$this, 'onMessage']);

        $this->slackClient->init();

        $this->slackClient->connect()->then([$this->slackClient, 'update']);

        $this->eventLoop->run();
    }

    public function onMessage(Payload $payload)
    {
        $data = $payload->getData();

        if (isset($data['subtype'])) {
            if ($data['subtype'] === 'message_changed') {
                $channel = $data['channel'];
                $data = $data['message'];
                $data['channel'] = $channel;
            } elseif ($data['subtype'] !== 'bot_message') {
                return;
            }
        }

        $this->logger->info('Received message', $data);

        $message = $this->messageFactory->create($this->slackClient, $data);

        $this->emit('message', [$message]);
    }

    public function quit()
    {
        $this->logger->info('Quitting');
        $this->slackClient->disconnect();
    }

    public function addPlugin($pluginId, PluginInterface $plugin)
    {
        if (isset($this->plugins[$pluginId])) {
            $this->logger->warning("$pluginId already added, ignoring duplicate.");
            return;
        }

        $this->plugins[$pluginId] = $plugin;
        $this->listeners[$pluginId] = $this->callOnMessage($pluginId, $plugin);

        $this->on('message', $this->listeners[$pluginId]);
    }

    public function removePlugin($pluginId)
    {
        if (isset($this->plugins[$pluginId])) {
            $this->removeListener('message', $this->listeners[$pluginId]);
            unset($this->plugins[$pluginId]);
            unset($this->listeners[$pluginId]);
        }
    }

    public function getHelp() : string
    {
        $help = [];
        foreach ($this->plugins as $key => $plugin) {
            /** @var PluginInterface $plugin */
            $help[] = "$key: " . $plugin->help();
        }

        return implode("\n", $help);
    }

    public function getStatus() : string
    {
        $count = count($this->plugins);

        $statuses = [];
        $statuses[] = "Yabot has $count plugins.";
        foreach ($this->plugins as $key => $plugin) {
            /** @var PluginInterface $plugin */
            $statuses[] = "$key: " . $plugin->status();
        }

        return implode("\n", $statuses);
    }

    protected function callOnMessage($pluginId, PluginInterface $plugin)
    {
        return function(MessageInterface $message) use ($pluginId, $plugin) {
            try {
                $plugin->onMessage($message);
            } catch (Exception $e) {
                $this->logger->warning("Unhandled Exception in $pluginId: ".$e->getMessage());
                $this->logger->warning($e->getTraceAsString());
            }
        };
    }
}
