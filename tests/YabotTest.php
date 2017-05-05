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
use Slack\Channel;
use Slack\Payload;
use Slack\User;

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
        $this->messageFactory = new MessageFactory();
    }

    public function testRun()
    {
        $slackClient = $this->newPartialMock(SlackClient::class, ['on', 'init', 'connect']);

        $yabot = new Yabot(
            $this->logger,
            $this->eventLoop,
            $slackClient,
            $this->messageFactory
        );

        $connectPromise = $this->newPartialMockWithExpectations(PromiseInterface::class, [
            ['then', ['params' => [(array)[$slackClient, 'update']]]],
        ]);

        $this->setAtExpectations($this->eventLoop, [['run', []]]);

        $this->setAtExpectations($slackClient, [
            ['on', ['params' => ['message', [$yabot, 'onMessage']]]],
            ['init', []],
            ['connect', ['result' => $connectPromise]],
        ]);

        $yabot->run();
    }

    public function onMessageDataProvider()
    {
        $data = [
            [
                [
                    'text' => 'this is a test',
                    'channel' => 'C0A2B3C4D',
                    'user' => 'U0290RGRD',
                ],
                '',
                'this is a test',
            ],
            [
                [
                    'text' => '<@U0290RGRD> this is a test',
                    'channel' => 'C0A2B3C4D',
                    'user' => 'U0290RGRD',
                ],
                '',
                '<@U0290RGRD> this is a test',
            ],
            [
                [
                    'text' => '<@USOMEUSER> this is a test',
                    'channel' => 'C0A2B3C4D',
                    'user' => 'U0290RGRD',
                ],
                '@someUser',
                'this is a test',
            ],
            [
                [
                    'text' => '<@UXXXXXXXX> this is a test',
                    'channel' => 'C0A2B3C4D',
                    'user' => 'U0290RGRD',
                ],
                '@someUser',
                null,
            ],
            [
                [
                    'text' => '<@UAUTHUSER> this is a test',
                    'channel' => 'C0A2B3C4D',
                    'user' => 'U0290RGRD',
                ],
                Message::AUTHED_USER,
                'this is a test',
            ],
            [
                [
                    'text' => '<@UXXXXXXXX> this is a test',
                    'channel' => 'C0A2B3C4D',
                    'user' => 'U0290RGRD',
                ],
                Message::AUTHED_USER,
                null,
            ],
            [
                [
                    'channel' => 'C0A2B3C4D',
                    'subtype' => 'message_changed',
                    'message' => [
                        'text' => 'this is a test',
                        'user' => 'U0290RGRD',
                    ],
                ],
                '',
                'this is a test',
            ],
            [
                [
                    'text' => 'this is a test',
                    'channel' => 'C0A2B3C4D',
                    'user' => 'U0290RGRD',
                    'subtype' => 'bot_message',
                ],
                '',
                'this is a test',
            ],
            [
                [
                    'text' => 'this is a test',
                    'channel' => 'C0A2B3C4D',
                    'user' => 'U0290RGRD',
                    'subtype' => 'message_deleted',
                ],
                '',
                null,
            ],
        ];

        return array_slice($data, 0, 100);
    }

    /**
     * @dataProvider onMessageDataProvider
     */
    public function testOnMessage($payloadData, $prefix, $dispatched)
    {
        $payload = $this->newPartialMockWithExpectations(Payload::class, [
            ['getData', ['result' => $payloadData]],
        ]);

        $expectedDispatch = $dispatched === null
            ? 'never'
            : ['params' => [$this->isInstanceOf(Message::class), $dispatched]];

        $plugin = $this->newPartialMockWithExpectations(PluginInterface::class, [
            ['help', 'never'],
            ['status', 'never'],
            ['init', 'never'],
            ['getPrefix', ['result' => $prefix]],
            ['dispatch', $expectedDispatch],
        ]);

        $someUser = $this->newPartialMockWithExpectations(User::class, [
            'getId' => ['invoked' => 'any', 'result' => 'USOMEUSER'],
        ]);
        $authedUser = $this->newPartialMockWithExpectations(User::class, [
            'getId' => ['invoked' => 'any', 'result' => 'UAUTHUSER'],
        ]);
        $user = $this->createMock(User::class);
        $channel = $this->createMock(Channel::class);

        $this->setExpectations($this->slackClient, [
            'userById' => ['invoked' => 'any', 'params' => [$payloadData['user'] ?? $payloadData['message']['user']], 'result' => $user],
            'channelById' => ['invoked' => 'any', 'params' => [$payloadData['channel']], 'result' => $channel],
            'userByName' => ['invoked' => 'any', 'params' => ['someUser'], 'result' => $someUser],
            'getAuthedUser' => ['invoked' => 'any', 'result' => $authedUser],
        ]);

        $plugins = [$plugin];

        $yabot = $this->newYabot();

        $yabot->init($plugins);

        $yabot->onMessage($payload);
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
        }

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
