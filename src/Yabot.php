<?php

namespace Nopolabs\Yabot;

use DateTime;
use Evenement\EventEmitterTrait;
use Exception;
use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\MessageFactory;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\SlackClient;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Slack\Payload;
use Slack\User;

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

        $this->addMemoryReporting();

        $this->eventLoop->run();
    }

    public function onMessage(Payload $payload)
    {
        $data = $payload->getData();

        if (isset($data['subtype'])) {
            if ($data['subtype'] === 'message_changed' && isset($data['message']['text'])) {
                $data['text'] = $data['message']['text'];
                $data['user'] = $data['message']['user'];
            } elseif ($data['subtype'] !== 'bot_message') {
                return;
            }
        }

        $this->logger->info('Received message', $data);

        try {
            $message = $this->messageFactory->create($this->slackClient, $data);
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
            $this->logger->warning($e->getTraceAsString());
            return;
        }

        if ($message->isSelf()) {
            return;
        }

        foreach ($this->prefixes as $prefix => $plugins) {
            if (!($matches = $message->matchesPrefix($prefix))) {
                continue;
            }

            $this->logger->debug('Matched prefix', ['prefix' => $prefix]);

            $text = ltrim($matches[1]);

            $message->setPluginText($text);

            foreach ($plugins as $pluginId => $plugin) {
                /** @var PluginInterface $plugin */
                try {
                    $this->logger->debug('dispatch', ['pluginId' => $pluginId, 'text' => $text]);
                    $plugin->dispatch($message);
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
            $prefix = $plugin->getPrefix();
            if ($prefix === Message::AUTHED_USER) {
                /** @var User $user */
                $user = $this->slackClient->getAuthedUser();
                $prefix = '@' . $user->getUsername();
            } elseif (!$prefix) {
                $prefix = '<none>';
            }
            /** @var PluginInterface $plugin */
            $help[] = $pluginId;
            $help[] = '  prefix: ' . $prefix;
            foreach (explode("\n", $plugin->help()) as $line) {
                $help[] = '    ' . $line;
            }
        }

        return implode("\n", $help);
    }

    public function getStatus() : string
    {
        $count = count($this->plugins);

        $statuses = [];
        $statuses[] = $this->getFormattedMemoryUsage();
        $statuses[] = "There are $count plugins loaded.";
        foreach ($this->plugins as $pluginId => $plugin) {
            /** @var PluginInterface $plugin */
            $statuses[] = "$pluginId " . $plugin->status();
        }

        return implode("\n", $statuses);
    }

    public function addTimer($interval, callable $callback)
    {
        $this->eventLoop->addTimer($interval, $callback);
    }

    public function addPeriodicTimer($interval, callable $callback)
    {
        $this->eventLoop->addPeriodicTimer($interval, $callback);
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

    protected function addMemoryReporting()
    {
        $now = new DateTime();
        $then = new DateTime('+1 hour');
        $then->setTime($then->format('H'), 0, 0);
        $delay = $then->getTimestamp() - $now->getTimestamp();

        $this->addTimer($delay, function() {
            $this->logger->info($this->getFormattedMemoryUsage());
            $this->addPeriodicTimer(3600, function () {
                $this->logger->info($this->getFormattedMemoryUsage());
            });
        });
    }

    protected function getFormattedMemoryUsage() : string
    {
        $memory = memory_get_usage() / 1024;
        $formatted = number_format($memory, 3).'K';
        return "Current memory usage: {$formatted}";
    }
}
