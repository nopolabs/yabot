<?php
namespace Nopolabs\Yabot\Tests;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\MessageFactory;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\SlackClient;
use Nopolabs\Yabot\Yabot;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Slack\Payload;

class YabotTest extends TestCase
{
    use MockWithExpectationsTrait;

    protected $logger;
    protected $eventLoop;
    protected $slackClient;
    protected $messageFactory;


    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventLoop = $this->createMock(LoopInterface::class);
        $this->slackClient = $this->createMock(SlackClient::class);
        $this->messageFactory = $this->createMock(MessageFactory::class);
    }

    public function testRun()
    {
        $slackClient = $this->newPartialMock(SlackClient::class, ['on', 'init', 'connect']);

        $yabot = $this->newYabot();

        $connectPromise = $this->newPartialMockWithExpectations(PromiseInterface::class, [
            ['then', [
                'oarams' => [$slackClient, 'update'],
            ]],
        ]);

        $this->setAtExpectations($this->eventLoop, [
            ['run', []],
        ]);

        $this->setAtExpectations($this->slackClient, [
            ['on', [
                'params' => ['message', [$yabot, 'onMessage']],
            ]],
            ['init', []],
            ['connect', [
                'result' => $connectPromise,
            ]],
        ]);

        $yabot->run();
    }

    public function onMessageDataProvider()
    {
        return [
            [
                [
                    'text' => 'this is a test',
                    'channel' => 'C0A2B3C4D',
                ],
                [
                    'text' => 'this is a test',
                    'channel' => 'C0A2B3C4D',
                ],
            ],
            [
                [
                    'channel' => 'C0A2B3C4D',
                    'subtype' => 'message_changed',
                    'message' => [
                        'text' => 'this is a test',
                    ],
                ],
                [
                    'text' => 'this is a test',
                    'channel' => 'C0A2B3C4D',
                ],
            ],
            [
                [
                    'text' => 'this is a test',
                    'channel' => 'C0A2B3C4D',
                    'subtype' => 'bot_message',
                ],
                [
                    'text' => 'this is a test',
                    'channel' => 'C0A2B3C4D',
                    'subtype' => 'bot_message',
                ],
            ],
            [
                [
                    'text' => 'this is a test',
                    'channel' => 'C0A2B3C4D',
                    'subtype' => 'message_deleted',
                ],
                null,
            ],
        ];
    }

    /**
     * @dataProvider onMessageDataProvider
     */
    public function testOnMessage($payloadData, $expectedData)
    {
        $payload = $this->newPartialMockWithExpectations(Payload::class, [
            ['getData', [
                'result' => $payloadData,
            ]],
        ]);

        if ($expectedData) {
            $message = $this->createMock(Message::class);
            $this->setAtExpectations($this->messageFactory, [
                ['create', [
                    'params' => [$this->slackClient, $expectedData],
                    'result' => $message,
                ]],
            ]);
            $emitExpectation = ['emit', ['params' => ['message', [$message]]]];
        } else {
            $this->setAtExpectations($this->messageFactory, [['create', 'never']]);
            $emitExpectation = ['emit', 'never'];
        }

        $yabot = $this->newYabot([$emitExpectation]);

        $yabot->onMessage($payload);
    }

    public function testAddPlugin()
    {
        $plugin = $this->createMock(PluginInterface::class);
        $wrapped = function() {};

        $yabot = $this->newYabot([
            ['wrapPlugin', ['params' => ['plugin-id', $plugin], 'result' => $wrapped]],
            ['on', ['params' => ['message', $wrapped]]],
        ]);

        $yabot->addPlugin('plugin-id', $plugin);
    }

    private function newYabot(array $expectations = []) : Yabot
    {
        if (empty($expectations)) {
            return new Yabot(
                $this->logger,
                $this->eventLoop,
                $this->slackClient,
                $this->messageFactory
            );
        } else {
            $constructorArgs = [
                $this->logger,
                $this->eventLoop,
                $this->slackClient,
                $this->messageFactory,
            ];

            return $this->newPartialMockWithExpectations(
                Yabot::class,
                $expectations,
                $constructorArgs
            );
        }
    }
}
