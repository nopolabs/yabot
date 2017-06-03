<?php
namespace Nopolabs\Yabot\Tests;

use Closure;
use Exception;
use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Message\MessageFactory;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginManager;
use Nopolabs\Yabot\Slack\Client;
use Nopolabs\Yabot\Yabot;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Slack\Payload;

class YabotTest extends TestCase
{
    use MockWithExpectationsTrait;

    private $logger;
    private $eventLoop;
    private $slackClient;
    private $messageFactory;
    private $pluginManager;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventLoop = $this->createMock(LoopInterface::class);
        $this->slackClient = $this->createMock(Client::class);
        $this->messageFactory = $this->createMock(MessageFactory::class);
        $this->pluginManager = $this->createMock(PluginManager::class);
    }

    public function testInit()
    {
        $plugin1 = $this->createMock(PluginInterface::class);
        $plugin2 = $this->createMock(PluginInterface::class);

        $plugins = [
            'plugin-1' => $plugin1,
            'plugin-2' => $plugin2,
        ];

        $this->setAtExpectations($this->pluginManager, [
            ['loadPlugin', ['params' => ['plugin-1', $this->identicalTo($plugin1)]]],
            ['loadPlugin', ['params' => ['plugin-2', $this->identicalTo($plugin2)]]],
        ]);

        $yabot = $this->newYabot();

        $yabot->init($plugins);
    }

    public function testInitWarning()
    {
        $plugin1 = $this->createMock(PluginInterface::class);

        $plugins = ['plugin-1' => $plugin1];

        $this->setAtExpectations($this->pluginManager, [
            ['loadPlugin', [
                'params' => ['plugin-1', $plugin1],
                'throws' => new Exception('boom!'),
            ]],
        ]);

        $this->setAtExpectations($this->logger, [
            ['info', ['params' => ['loading plugin-1']]],
            ['warning', ['params' => ['Unhandled Exception while loading plugin-1: boom!']]],
            ['warning', ['params' => [$this->isType('string')]]],
        ]);

        $yabot = $this->newYabot();

        $yabot->init($plugins);
    }

    public function testRun()
    {
        $yabot = $this->newYabot([
            ['getSlack', ['result' => $this->slackClient]],
            ['addMemoryReporting'],
        ]);

        $promise = $this->newPartialMockWithExpectations(PromiseInterface::class, [
            ['then', ['params' => [[$yabot, 'connected']]]]
        ]);

        $this->setAtExpectations($this->slackClient, [
            ['init'],
            ['connect', ['result' => $promise]],
        ]);

        $this->setAtExpectations($this->eventLoop, [
            ['run'],
        ]);

        $yabot->run();
    }

    public function testConnected()
    {
        $yabot = $this->newYabot([
            ['getSlack', ['result' => $this->slackClient]],
        ]);

        $this->setAtExpectations($this->slackClient, [
            ['update', ['params' => [$this->isInstanceOf(Closure::class)]]],
            ['onEvent', ['params' => ['message', [$yabot, 'onMessage']]]],
        ]);

        $yabot->connected();
    }

    public function testOnMessage()
    {
        $data = ['test' => 'data'];

        /** @var Payload $payload */
        $payload = $this->newPartialMockWithExpectations(Payload::class, [
            ['getData', ['result' => $data]],
        ]);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isSelf', ['result' => false]],
        ]);

        $this->setAtExpectations($this->messageFactory, [
            ['create', ['params' => [$data], 'result' => $message]],
        ]);

        $this->setAtExpectations($this->pluginManager, [
            ['dispatchMessage', ['params' => [$message]]],
        ]);

        $yabot = $this->newYabot();

        $yabot->onMessage($payload);
    }

    public function testOnMessage_isSelf()
    {
        $data = ['test' => 'data'];

        /** @var Payload $payload */
        $payload = $this->newPartialMockWithExpectations(Payload::class, [
            ['getData', ['result' => $data]],
        ]);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isSelf', ['result' => true]],
        ]);

        $this->setAtExpectations($this->messageFactory, [
            ['create', ['params' => [$data], 'result' => $message]],
        ]);

        $this->setAtExpectations($this->pluginManager, [
            ['dispatchMessage', 'never'],
        ]);

        $yabot = $this->newYabot();

        $yabot->onMessage($payload);
    }

    public function testOnMessage_createException()
    {
        $data = ['test' => 'data'];

        /** @var Payload $payload */
        $payload = $this->newPartialMockWithExpectations(Payload::class, [
            ['getData', ['result' => $data]],
        ]);

        $this->setAtExpectations($this->messageFactory, [
            ['create', ['params' => [$data], 'throws' => new Exception('boom!')]],
        ]);

        $this->setAtExpectations($this->pluginManager, [
            ['dispatchMessage', 'never'],
        ]);

        $yabot = $this->newYabot();

        $yabot->onMessage($payload);
    }

    public function testGetHelp()
    {
        $this->setAtExpectations($this->pluginManager, [
            ['getHelp', ['result' => ['line 1', 'line 2']]]
        ]);

        $this->assertSame("line 1\nline 2", $this->newYabot()->getHelp());
    }

    public function testGetStatus()
    {
        $this->setAtExpectations($this->pluginManager, [
            ['getStatuses', ['result' => ['line 1', 'line 2']]]
        ]);

        $yabot = $this->newYabot([
            ['getFormattedMemoryUsage', ['result' => 'memory usage']],
        ]);

        $this->assertSame("memory usage\nline 1\nline 2", $yabot->getStatus());
    }

    public function testAddTimer()
    {
        $callable = function () { return; };

        $this->setAtExpectations($this->eventLoop, [
            ['addTimer', ['params' => [60, $callable]]],
        ]);

        $this->newYabot()->addTimer(60, $callable);
    }

    public function testAddPeriodicTimer()
    {
        $callable = function () { return; };

        $this->setAtExpectations($this->eventLoop, [
            ['addPeriodicTimer', ['params' => [60, $callable]]],
        ]);

        $this->newYabot()->addPeriodicTimer(60, $callable);
    }

    private function newYabot(array $expectations = []) : Yabot
    {
        if (empty($expectations)) {
            return new Yabot(
                $this->logger,
                $this->eventLoop,
                $this->slackClient,
                $this->messageFactory,
                $this->pluginManager
            );
        }

        return $this->newPartialMockWithExpectations(
            Yabot::class,
            $expectations,
            [
                $this->logger,
                $this->eventLoop,
                $this->slackClient,
                $this->messageFactory,
                $this->pluginManager,
            ]
        );
    }
}
