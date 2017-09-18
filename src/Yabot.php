<?php

namespace Nopolabs\Yabot;

use DateTime;
use Exception;
use Nopolabs\Yabot\Helpers\ConfigTrait;
use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Helpers\LoopTrait;
use Nopolabs\Yabot\Helpers\SlackTrait;
use Nopolabs\Yabot\Message\MessageFactory;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginManager;
use Nopolabs\Yabot\Slack\Client;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Promise\Timer;
use Slack\Payload;
use Slack\User;
use Throwable;

class Yabot
{
    use LogTrait;
    use LoopTrait;
    use SlackTrait;
    use ConfigTrait;

    /** @var MessageFactory */
    private $messageFactory;

    /** @var PluginManager */
    private $pluginManager;

    /** @var string */
    private $messageLog;

    /** @var TimerInterface */
    private $monitor;

    /** @var bool */
    private $pong;

    public function __construct(
        LoggerInterface $logger,
        LoopInterface $eventLoop,
        Client $slackClient,
        MessageFactory $messageFactory,
        PluginManager $pluginManager,
        array $config = []
    ) {
        $this->setLog($logger);
        $this->setLoop($eventLoop);
        $this->setSlack($slackClient);
        $this->setConfig($config);
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

        $this->getLog()->info('Connecting...');

        Timer\timeout($slack->connect(), 30, $this->getLoop())
            ->then(function () {
                $this->getLog()->info('Connected.');
                $this->connected();
            })
            ->otherwise(function (Timer\TimeoutException $error) {
                $this->getLog()->error($error->getMessage());
                $this->getLog()->error('Connection failed, shutting down.');
                $this->shutDown();
            })
            ->otherwise(function ($error) {
                $this->getLog()->error('Connection failed, shutting down.');
                $this->shutDown();
            });

        $this->addMemoryReporting();

        $this->getLoop()->run();
    }

    public function shutDown()
    {
        $this->getLog()->error('Shutting down...');

        $this->getSlack()->disconnect();
        $this->getLoop()->stop();
    }

    public function reconnect()
    {
        $this->getLog()->error('Reconnecting...');

        if ($this->monitor) {
            $this->loop->cancelTimer($this->monitor);
        }

        $this->getSlack()->reconnect()->then(
            function () {
                $this->getLog()->info('Reconnected');
                $this->monitor = $this->startConnectionMonitor();
            },
            function () {
                $this->getLog()->error('Reconnect failed, shutting down.');
                $this->shutDown();
            }
        );
    }

    public function connected()
    {
        $slack = $this->getSlack();

        $slack->update(function(User $authedUser) {
            $this->pluginManager->setAuthedUser($authedUser);
        });

        $slack->onEvent('message', [$this, 'onMessage']);
        $slack->onEvent('team_join', [$this, 'onTeamJoin']);

        $this->monitor = $this->startConnectionMonitor();
    }

    public function onMessage(Payload $payload)
    {
        $data = $payload->getData();

        $this->debug('Received message', $data);

        try {
            $this->logMessage($data);
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

    public function onTeamJoin(Payload $payload)
    {
        $this->getSlack()->updateUsers();
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

    protected function logMessage($data)
    {
        if ($this->messageLog !== null) {
            file_put_contents($this->messageLog, json_encode($data) . "\n", FILE_APPEND);
        }
    }

    /**
     * @return TimerInterface|null
     */
    protected function startConnectionMonitor()
    {
        if ($interval = $this->get('connection_monitor.interval')) {

            $this->getLog()->info("Monitoring websocket connection every $interval seconds.");
            $this->notify("Monitoring websocket connection every $interval seconds.");

            $this->ping();

            return $this->loop->addPeriodicTimer($interval, function () {
                $this->checkPong();
                $this->ping();
            });
        }
    }

    protected function checkPong()
    {
        if (!$this->pong) {
            $this->getLog()->error('No pong.');

            $failureStrategy = $this->get('connection_monitor.failure_strategy', 'reconnect');

            if ($failureStrategy === 'reconnect') {
                $this->reconnect();
                return;
            }

            if ($failureStrategy !== 'shutdown') {
                $this->getLog()->error("Unknown connection_monitor.failure_strategy '$failureStrategy'");
            }

            $this->shutDown();
        }
    }

    protected function ping()
    {
        $this->pong = false;

        $this->getSlack()->ping()
            ->then(
                function (Payload $payload) {
                    $this->getLog()->info($payload->toJson());
                    $this->pong = true;
                }
            );
    }

    protected function notify(string $message)
    {
        if ($user = $this->get('notify.user')) {
            $this->getSlack()->directMessage($message, $user);
        }
    }
}
