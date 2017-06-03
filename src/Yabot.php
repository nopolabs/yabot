<?php

namespace Nopolabs\Yabot;

use DateTime;
use Exception;
use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Helpers\SlackTrait;
use Nopolabs\Yabot\Message\MessageFactory;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginManager;
use Nopolabs\Yabot\Slack\Client;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Slack\Payload;
use Slack\User;
use Throwable;

class Yabot
{
    use LogTrait;
    use SlackTrait;

    /** @var LoopInterface */
    private $eventLoop;

    /** @var MessageFactory */
    private $messageFactory;

    /** @var PluginManager */
    private $pluginManager;

    private $messageLog;

    public function __construct(
        LoggerInterface $logger,
        LoopInterface $eventLoop,
        Client $slackClient,
        MessageFactory $messageFactory,
        PluginManager $pluginManager
    ) {
        $this->setLog($logger);
        $this->setSlack($slackClient);
        $this->eventLoop = $eventLoop;
        $this->messageFactory = $messageFactory;
        $this->pluginManager = $pluginManager;
        $this->messageLog = null;
    }

    public function getMessageLog()
    {
        return $this->messageLog;
    }

    public function setMessageLog(string $messageLog = null)
    {
        $this->messageLog = $messageLog ?? null;
    }

    public function init(array $plugins)
    {
        foreach ($plugins as $pluginId => $plugin) {
            /** @var PluginInterface $plugin */

            $this->info("loading $pluginId");

            try {
                $this->pluginManager->loadPlugin($pluginId, $plugin);
            } catch (Exception $e) {
                $this->warning("Unhandled Exception while loading $pluginId: ".$e->getMessage());
                $this->warning($e->getTraceAsString());
            }
        }
    }

    public function run()
    {
        $slack = $this->getSlack();

        $slack->init();

        $slack->connect()->then([$this, 'connected']);

        $this->addMemoryReporting();

        $this->eventLoop->run();
    }

    public function connected()
    {
        $slack = $this->getSlack();

        $slack->update(function(User $authedUser) {
            $this->pluginManager->updatePrefixes($authedUser->getUsername());
        });

        $slack->onEvent('message', [$this, 'onMessage']);
    }

    public function onMessage(Payload $payload)
    {
        $data = $payload->getData();

        $this->debug('Received message', $data);

        try {
            if ($this->messageLog !== null) {
                $this->logMessage($data);
            }
            $message = $this->messageFactory->create($data);
        } catch (Throwable $throwable) {
            $errmsg = $throwable->getMessage()."\n"
                .$throwable->getTraceAsString()."\n"
                ."Payload data: ".json_encode($data);
            $this->warning($errmsg);
            return;
        }

        if ($message->isSelf()) {
            return;
        }

        $this->pluginManager->dispatchMessage($message);
    }

    public function getHelp() : string
    {
        return implode("\n", $this->pluginManager->getHelp());
    }

    public function getStatus() : string
    {
        $statuses = $this->pluginManager->getStatuses();

        array_unshift($statuses, $this->getFormattedMemoryUsage());

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

    protected function addMemoryReporting()
    {
        $now = new DateTime();
        $then = new DateTime('+1 hour');
        $then->setTime($then->format('H'), 0, 0);
        $delay = $then->getTimestamp() - $now->getTimestamp();

        $this->addTimer($delay, function() {
            $this->info($this->getFormattedMemoryUsage());
            $this->addPeriodicTimer(3600, function() {
                $this->info($this->getFormattedMemoryUsage());
            });
        });
    }

    protected function getFormattedMemoryUsage() : string
    {
        $memory = memory_get_usage() / 1024;
        $formatted = number_format($memory, 3).'K';
        return "Current memory usage: {$formatted}";
    }

    private function logMessage($data)
    {
        file_put_contents($this->messageLog, json_encode($data)."\n", FILE_APPEND);
    }
}
