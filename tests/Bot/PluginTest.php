<?php

namespace Nopolabs\Yabot\Tests\Bot;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Bot\Message;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slack\Channel;
use Slack\User;

class PluginTest extends TestCase
{
    use MockWithExpectationsTrait;

    /** @var TestPlugin */
    private $plugin;

    private $logger;

    private $channel;

    private $user;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->plugin = new TestPlugin($this->logger);
        $this->channel = $this->createMock(Channel::class);
        $this->user = $this->createMock(User::class);
    }

    public function testHelp()
    {
        $this->assertSame('no help available', $this->plugin->help());
    }

    public function testStatus()
    {
        $this->assertSame('running', $this->plugin->status());
    }

    public function initDataProvider() : array
    {
        $base = [
            'prefix' => '',
            'isBot' => null,
            'channel' => '',
            'user' => '',
            'matchers' => [],
        ];

        return [
            [[], $base],
            [['prefix' => Message::AUTHED_USER], array_merge($base, ['prefix' => Message::AUTHED_USER])],
            [['isBot' => null], array_merge($base, ['isBot' => null])],
            [['isBot' => true], array_merge($base, ['isBot' => true])],
            [['isBot' => false], array_merge($base, ['isBot' => false])],
            [['channel' => ''], array_merge($base, ['channel' => ''])],
            [['channel' => 'general'], array_merge($base, ['channel' => 'general'])],
            [['channel' => ['general','special']], array_merge($base, ['channel' => ['general','special']])],
            [['user' => ''], array_merge($base, ['user' => ''])],
            [['user' => 'alice'], array_merge($base, ['user' => 'alice'])],
            [['user' => ['alice','bob']], array_merge($base, ['user' => ['alice','bob']])],
            [
                ['matchers' => ['testMethod' => '/^test/']],
                array_merge(
                    $base,
                    ['matchers' => [
                        'testMethod' => [
                            'patterns' => ['/^test/'],
                            'isBot' => null,
                            'channel' => '',
                            'user' => '',
                            'method' => 'testMethod',
                        ],
                    ]]
                )
            ],
            [
                ['matchers' => [
                    'matcherName' => [
                        'patterns' => ['/^test/'],
                        'method' => 'testMethod',
                    ],
                ]],
                array_merge(
                    $base,
                    ['matchers' => [
                        'matcherName' => [
                            'patterns' => ['/^test/'],
                            'isBot' => null,
                            'channel' => '',
                            'user' => '',
                            'method' => 'testMethod',
                        ],
                    ]]
                )
            ],
            [
                ['matchers' => ['testMethod' => '/^test /', 'test2Method' => '/^test2 /']],
                array_merge(
                    $base,
                    ['matchers' => [
                        'testMethod' => [
                            'patterns' => ['/^test /'],
                            'isBot' => null,
                            'channel' => '',
                            'user' => '',
                            'method' => 'testMethod',
                        ],
                        'test2Method' => [
                            'patterns' => ['/^test2 /'],
                            'isBot' => null,
                            'channel' => '',
                            'user' => '',
                            'method' => 'test2Method',
                        ],
                    ]]
                )
            ],
            [
                ['matchers' => [
                    'testMethod' => '/^test /',
                    'something' => [
                        'patterns' => ['/^test2 /', '/^2 /'],
                        'isBot' => false,
                        'channel' => 'general',
                        'user' => 'alice',
                        'method' => 'test2Method',
                    ],
                ]],
                array_merge(
                    $base,
                    ['matchers' => [
                        'testMethod' => [
                            'patterns' => ['/^test /'],
                            'isBot' => null,
                            'channel' => '',
                            'user' => '',
                            'method' => 'testMethod',
                        ],
                        'something' => [
                            'patterns' => ['/^test2 /', '/^2 /'],
                            'isBot' => false,
                            'channel' => 'general',
                            'user' => 'alice',
                            'method' => 'test2Method',
                        ],
                    ]]
                )
            ],
        ];
    }

    /**
     * @dataProvider initDataProvider
     */
    public function testInit(array $params, array $expected)
    {
        $this->plugin->init('plugin.test', $params);

        $this->assertEquals($expected, $this->plugin->getConfigTest());
    }

    public function testGetNoPrefix()
    {
        $this->plugin->init('plugin.test', []);

        $this->assertEquals('', $this->plugin->getPrefix());
    }

    public function testGetPrefix()
    {
        $this->plugin->init('plugin.test', ['prefix' => '@alice']);

        $this->assertEquals('@alice', $this->plugin->getPrefix());
    }

    public function testDispatchIsHandledShortCut()
    {
        $this->plugin->init('plugin.test', ['matchers' => ['testMethod' => '/^test /']]);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => true]],
            ['matchesIsBot', 'never'],
            ['matchesChannel', 'never'],
            ['matchesUser', 'never'],
            ['matchPatterns', 'never'],
        ]);

        $text = 'this is a test';

        $this->plugin->dispatch($message, $text);
    }

    public function testDispatchIsBotShortCut()
    {
        $this->plugin->init('plugin.test', [
            'isBot' => true,
            'matchers' => ['testMethod' => '/^test /'],
        ]);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesIsBot', ['params' => [true], 'result' => false]],
            ['matchesChannel', 'never'],
            ['matchesUser', 'never'],
            ['matchPatterns', 'never'],
        ]);
        $message->expects($this->exactly(1))
            ->method('matchesIsBot');

        $text = 'this is a test';

        $this->plugin->dispatch($message, $text);
    }

    public function testDispatchChannelShortCut()
    {
        $this->plugin->init('plugin.test', [
            'channel' => 'general',
            'matchers' => ['testMethod' => '/^test /'],
        ]);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => ['general'], 'result' => false]],
            ['getChannel', ['result' => $this->channel]],
            ['matchesUser', 'never'],
            ['matchPatterns', 'never'],
        ]);
        $message->expects($this->exactly(1))
            ->method('matchesIsBot');

        $text = 'this is a test';

        $this->plugin->dispatch($message, $text);
    }

    public function testDispatchUserShortCut()
    {
        $this->plugin->init('plugin.test', [
            'user' => 'alice',
            'matchers' => ['testMethod' => '/^test /'],
        ]);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => ['alice'], 'result' => false]],
            ['getUser', ['result' => $this->user]],
            ['matchPatterns', 'never'],
        ]);
        $message->expects($this->exactly(1))
            ->method('matchesIsBot');

        $text = 'this is a test';

        $this->plugin->dispatch($message, $text);
    }

    public function testDispatchMatcherIsBotShortCut()
    {
        $this->plugin->init('plugin.test', [
            'matchers' => [
                'testing' => [
                    'patterns' => ['/^test /'],
                    'isBot' => true,
                    'channel' => '',
                    'user' => '',
                    'method' => 'testMethod',
                ],
                'that' => [
                    'patterns' => ['/^that (.*)/'],
                    'isBot' => true,
                    'channel' => '',
                    'user' => '',
                    'method' => 'thatMethod',
                ],
            ],
        ]);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchesIsBot', ['params' => [true], 'result' => false]],
            ['matchesIsBot', ['params' => [true], 'result' => false]],
            ['matchPatterns', 'never'],
        ]);
        $message->expects($this->exactly(3))
            ->method('matchesIsBot');

        $text = 'this is a test';

        $this->plugin->dispatch($message, $text);
    }

    public function testDispatchMatcherChannelShortCut()
    {
        $this->plugin->init('plugin.test', [
            'matchers' => [
                'testing' => [
                    'patterns' => ['/^test /'],
                    'isBot' => null,
                    'channel' => 'general',
                    'user' => '',
                    'method' => 'testMethod',
                ],
                'that' => [
                    'patterns' => ['/^that (.*)/'],
                    'isBot' => true,
                    'channel' => '',
                    'user' => '',
                    'method' => 'thatMethod',
                ],
            ],
        ]);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => ['general'], 'result' => false]],
            ['matchesIsBot', ['params' => [true], 'result' => false]],
            ['matchPatterns', 'never'],
        ]);
        $message->expects($this->exactly(3))
            ->method('matchesIsBot');

        $text = 'this is a test';

        $this->plugin->dispatch($message, $text);
    }

    public function testDispatchMatcherUserShortCut()
    {
        $this->plugin->init('plugin.test', [
            'matchers' => [
                'testing' => [
                    'patterns' => ['/^test /'],
                    'isBot' => null,
                    'channel' => '',
                    'user' => 'alice',
                    'method' => 'testMethod',
                ],
                'that' => [
                    'patterns' => ['/^that (.*)/'],
                    'isBot' => true,
                    'channel' => '',
                    'user' => '',
                    'method' => 'thatMethod',
                ],
            ],
        ]);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => ['alice'], 'result' => false]],
            ['matchesIsBot', ['params' => [true], 'result' => false]],
            ['matchPatterns', 'never'],
        ]);
        $message->expects($this->exactly(3))
            ->method('matchesIsBot');

        $text = 'this is a test';

        $this->plugin->dispatch($message, $text);
    }

    public function testDispatchMatcherPatternsShortCut()
    {
        $this->plugin->init('plugin.test', [
            'matchers' => [
                'testing' => [
                    'patterns' => ['/^test /'],
                    'isBot' => null,
                    'channel' => '',
                    'user' => '',
                    'method' => 'testMethod',
                ],
                'that' => [
                    'patterns' => ['/^that (.*)/'],
                    'isBot' => true,
                    'channel' => '',
                    'user' => '',
                    'method' => 'thatMethod',
                ],
            ],
        ]);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchPatterns', ['params' => [['/^test /']], 'result' => []]],
            ['matchesIsBot', ['params' => [true], 'result' => false]],
        ]);
        $message->expects($this->exactly(3))
            ->method('matchesIsBot');

        $text = 'this is a test';

        $this->plugin->dispatch($message, $text);
    }

    public function testInvalidMatchShortcut()
    {
        $matches = ['this is a test', 'is a test'];

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchPatterns', ['params' => [['/^this (.*)/']], 'result' => $matches]],
        ]);
        $message->expects($this->exactly(2))
            ->method('matchesIsBot');

        $params = [
            'patterns' => ['/^this (.*)/'],
            'isBot' => null,
            'channel' => '',
            'user' => '',
            'method' => 'testMethod',
        ];

        $plugin = $this->newPartialMockWithExpectations(
            TestPlugin::class,
            [
                ['validMatch', ['params' => [$message, $params, $matches], 'result' => false]],
                ['dispatchMessage', 'never'],
            ],
            [$this->logger]
        );

        $plugin->init('plugin.test', [
            'matchers' => [
                'testing' => $params,
            ],
        ]);

        $text = 'this is a test';

        $plugin->dispatch($message, $text);
    }

    public function testDispatchMessage()
    {
        $matches = ['this is a test', 'is a test'];

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchPatterns', ['params' => [['/^this (.*)/']], 'result' => $matches]],
        ]);
        $message->expects($this->exactly(2))
            ->method('matchesIsBot');

        $plugin = $this->newPartialMockWithExpectations(
            TestPlugin::class,
            [
                ['dispatchMessage', ['params' => [$message, ['testMethod', $matches]]]],
            ],
            [$this->logger]
        );

        $plugin->init('plugin.test', [
            'matchers' => [
                'testing' => [
                    'patterns' => ['/^this (.*)/'],
                    'isBot' => null,
                    'channel' => '',
                    'user' => '',
                    'method' => 'testMethod',
                ],
            ],
        ]);

        $text = 'this is a test';

        $plugin->dispatch($message, $text);
    }

    public function testDispatchMessageIsHandledShortCut()
    {
        $matches = ['this is a test', 'is a test'];

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchPatterns', ['params' => [['/^this (.*)/']], 'result' => $matches]],
            ['isHandled', ['result' => true]],
        ]);
        $message->expects($this->exactly(2))
            ->method('matchesIsBot');

        $plugin = $this->newPartialMockWithExpectations(
            TestPlugin::class,
            [
                ['dispatchMessage', ['params' => [$message, ['testMethod', $matches]]]],
            ],
            [$this->logger]
        );

        $plugin->init('plugin.test', [
            'matchers' => [
                'testing' => [
                    'patterns' => ['/^this (.*)/'],
                    'isBot' => null,
                    'channel' => '',
                    'user' => '',
                    'method' => 'testMethod',
                ],
                'that' => [
                    'patterns' => ['/^that (.*)/'],
                    'isBot' => null,
                    'channel' => '',
                    'user' => '',
                    'method' => 'thatMethod',
                ],
            ],
        ]);

        $text = 'this is a test';

        $plugin->dispatch($message, $text);
    }

    public function testDispatchMessageMatchesSecondMatcher()
    {
        $matches = ['that is a test', 'is a test'];

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchPatterns', ['params' => [['/^this (.*)/']], 'result' => []]],
            ['matchesIsBot', ['params' => [null], 'result' => true]],
            ['matchesChannel', ['params' => [''], 'result' => true]],
            ['matchesUser', ['params' => [''], 'result' => true]],
            ['matchPatterns', ['params' => [['/^that (.*)/']], 'result' => $matches]],
            ['isHandled', ['result' => true]],
        ]);
        $message->expects($this->exactly(3))
            ->method('matchesIsBot');

        $plugin = $this->newPartialMockWithExpectations(
            TestPlugin::class,
            [
                ['dispatchMessage', ['params' => [$message, ['thatMethod', $matches]]]],
            ],
            [$this->logger]
        );

        $plugin->init('plugin.test', [
            'matchers' => [
                'testing' => [
                    'patterns' => ['/^this (.*)/'],
                    'isBot' => null,
                    'channel' => '',
                    'user' => '',
                    'method' => 'testMethod',
                ],
                'that' => [
                    'patterns' => ['/^that (.*)/'],
                    'isBot' => null,
                    'channel' => '',
                    'user' => '',
                    'method' => 'thatMethod',
                ],
            ],
        ]);

        $text = 'that is a test';

        $plugin->dispatch($message, $text);
    }
}