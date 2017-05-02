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
    protected $prefixes;

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
        $this->prefixes = [];
    }

    public function init(array $plugins)
    {
        foreach ($plugins as $pluginId => $plugin) {
            /** @var PluginInterface $plugin */

            $this->logger->info("loading $pluginId");

            try {
                $this->loadPlugin($pluginId, $plugin);
            } catch (Exception $e) {
                $this->logger->warning("Unhandled Exception while loading $pluginId: ".$e->getMessage());
                $this->logger->warning($e->getTraceAsString());
            }
        }
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
            if ($data['subtype'] === 'message_changed' && isset($data['message']['text'])) {
                $data['text'] = $data['message']['text'];
            } elseif ($data['subtype'] !== 'bot_message') {
                return;
            }
        }

        $this->logger->info('Received message', $data);

        $message = $this->messageFactory->create($this->slackClient, $data);

        if ($message->isSelf()) {
            return;
        }

        foreach ($this->prefixes as $prefix => $plugins) {
            if (!($matches = $message->matchesPrefix($prefix))) {
                continue;
            }

            $this->logger->debug('Matched prefix', ['prefix' => $prefix]);

            $text = ltrim($matches[1]);

            foreach ($plugins as $pluginId => $plugin) {
                /** @var PluginInterface $plugin */
                try {
                    $this->logger->debug('dispatch', ['pluginId' => $pluginId, 'text' => $text]);

                    $plugin->dispatch($message, $text);
                } catch (Exception $e) {
                    $this->logger->warning("Unhandled Exception in $pluginId: ".$e->getMessage());
                    $this->logger->warning($e->getTraceAsString());
                }

                if ($message->isHandled()) {
                    return;
                }
            }

            if ($message->isHandled()) {
                return;
            }
        }
    }

    public function quit()
    {
        $this->logger->info('Quitting');
        $this->slackClient->disconnect();
    }

    public function getHelp() : string
    {
        $help = [];
        foreach ($this->plugins as $pluginId => $plugin) {
            /** @var PluginInterface $plugin */
            $help[] = "$pluginId " . $plugin->help();
        }

        return implode("\n", $help);
    }

    public function getStatus() : string
    {
        $count = count($this->plugins);

        $statuses = [];
        $statuses[] = "Yabot has $count plugins.";
        foreach ($this->plugins as $pluginId => $plugin) {
            /** @var PluginInterface $plugin */
            $statuses[] = "$pluginId " . $plugin->status();
        }

        return implode("\n", $statuses);
    }

    protected function loadPlugin($pluginId, PluginInterface $plugin)
    {
        if (isset($this->plugins[$pluginId])) {
            $this->logger->warning("$pluginId already loaded, ignoring duplicate.");
            return;
        }

        $this->plugins[$pluginId] = $plugin;

        $prefix = $plugin->getPrefix();

        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }

        $this->prefixes[$prefix][$pluginId] = $plugin;

        $this->logger->info('loaded', ['pluginId' => $pluginId, 'prefix' => $prefix]);
    }
}
