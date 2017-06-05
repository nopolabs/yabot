<?php

namespace Nopolabs\Yabot\Tests\Plugin;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\MethodMatcher;
use Nopolabs\Yabot\Plugin\PluginManager;
use Nopolabs\Yabot\Plugin\PluginMatcher;
use Nopolabs\Yabot\Plugin\PluginTrait;
use Nopolabs\Yabot\Yabot;
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
            'prefix' => PluginManager::NO_PREFIX,
            'isBot' => null,
            'channels' => [],
            'users' => [],
            'matchers' => [],
            'priority' => PluginManager::DEFAULT_PRIORITY,
        ];

        $data = [
            [[], $base],
            [['prefix' => PluginManager::AUTHED_USER_PREFIX], array_merge($base, ['prefix' => PluginManager::AUTHED_USER_PREFIX])],
            [['isBot' => null], array_merge($base, ['isBot' => null])],
            [['isBot' => true], array_merge($base, ['isBot' => true])],
            [['isBot' => false], array_merge($base, ['isBot' => false])],
            [['channel' => ''], array_merge($base, ['channels' => []])],
            [['channel' => 'general'], array_merge($base, ['channels' => ['general']])],
            [['channels' => ['general','special']], array_merge($base, ['channels' => ['general','special']])],
            [['user' => ''], array_merge($base, ['users' => []])],
            [['user' => 'alice'], array_merge($base, ['users' => ['alice']])],
            [['users' => ['alice','bob']], array_merge($base, ['users' => ['alice','bob']])],
            [
                ['matchers' => ['testMethod' => '/^test/']],
                array_merge(
                    $base,
                    ['matchers' => [
                        'testMethod' => [
                            'patterns' => ['/^test/'],
                            'isBot' => null,
                            'channels' => [],
                            'users' => [],
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
                            'channels' => [],
                            'users' => [],
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
                            'channels' => [],
                            'users' => [],
                            'method' => 'testMethod',
                        ],
                        'test2Method' => [
                            'patterns' => ['/^test2 /'],
                            'isBot' => null,
                            'channels' => [],
                            'users' => [],
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
                        'channels' => ['general'],
                        'users' => ['alice'],
                        'method' => 'test2Method',
                    ],
                ]],
                array_merge(
                    $base,
                    ['matchers' => [
                        'testMethod' => [
                            'patterns' => ['/^test /'],
                            'isBot' => null,
                            'channels' => [],
                            'users' => [],
                            'method' => 'testMethod',
                        ],
                        'something' => [
                            'patterns' => ['/^test2 /', '/^2 /'],
                            'isBot' => false,
                            'channels' => ['general'],
                            'users' => ['alice'],
                            'method' => 'test2Method',
                        ],
                    ]]
                )
            ],
        ];

        return array_slice($data, 0, 100);
    }

    /**
     * @dataProvider initDataProvider
     */
    public function testInit(array $params, array $expected)
    {
        $this->plugin->init('plugin.test', $params);

        $this->assertEquals($expected, $this->plugin->getConfigTest());
    }

    public function testReplaceInPatterns()
    {
        $search = ' ';
        $replace = '\\s+';
        $matchers = [
            'test' => ['patterns' => ['one two', 'three four five']],
        ];
        $expected = [
            'test' => ['patterns' => ['one\\s+two', 'three\\s+four\\s+five']],
        ];

        $replaced = $this->plugin->replaceInPatterns($search, $replace, $matchers);

        $this->assertEquals($expected, $replaced);
    }

    public function testGetDefaultPriotity()
    {
        $this->plugin->init('plugin.test', []);

        $this->assertEquals(PluginManager::DEFAULT_PRIORITY, $this->plugin->getPriority());
    }

    public function testGetPriority()
    {
        $this->plugin->init('plugin.test', ['priority' => 1]);

        $this->assertEquals(1, $this->plugin->getPriority());
    }

    public function testGetNoPrefix()
    {
        $this->plugin->init('plugin.test', []);

        $this->assertEquals(PluginManager::NO_PREFIX, $this->plugin->getPrefix());
    }

    public function testGetPrefix()
    {
        $this->plugin->init('plugin.test', ['prefix' => '@alice']);

        $this->assertEquals('@alice', $this->plugin->getPrefix());
    }

    public function handleDataProvider()
    {
        return [
            [
                [],
                [],
                [],
                [],
                [],
            ],
            [
                [],
                ['one two'],
                [[]],
                [[]],
                [],
            ],
            [
                [true],
                ['one two'],
                [['one two', 'one']],
                [], // methodMatcher2 not called because message was handled
                [['method-1', ['one two', 'one']]],
            ],
            [
                [false],
                ['one two'],
                [['one two', 'one']],
                [[]],
                [['method-1', ['one two', 'one']]],
            ],
            [
                [false],
                ['one two'],
                [[]],
                [['one two', 'two']],
                [['method-2', ['one two', 'two']]],
            ],
            [
                [false, false],
                ['one two'],
                [['one two', 'one']],
                [['one two', 'two']],
                [
                    ['method-1', ['one two', 'one']],
                    ['method-2', ['one two', 'two']],
                ],
            ],
        ];
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(
        array $messageIsHandled,
        array $pluginMatches,
        array $matcher1Matches,
        array $matcher2Matches,
        array $dispatchExpected)
    {
        $expectations = array_reduce(
            $messageIsHandled,
            function($expectations, $isHandled) {
                $expectations[] = ['isHandled', ['result' => $isHandled]];
                return $expectations;
            },
            []
        );
        /** @var Message $message */
        $message = $this->newPartialMockWithExpectations(Message::class, $expectations);

        /** @var PluginMatcher $pluginMatcher */
        $pluginMatcher = $this->newPartialMockWithExpectations(PluginMatcher::class, [
            ['matches', ['params' => [$message], 'result' => $pluginMatches]],
        ]);

        $expectations = array_reduce(
            $matcher1Matches,
            function($expectations, $matches) use ($message) {
                $expectations[] = ['matches', ['params' => [$message], 'result' => $matches]];
                if (!empty($matches)) {
                    $expectations[] = ['getMethod', ['result' => 'method-1']];
                    $expectations[] = ['getName', ['result' => 'name-1']];
                }
                return $expectations;
            },
            []
        );
        $methodMatcher1 = $this->newPartialMockWithExpectations(MethodMatcher::class, $expectations);

        $expectations = array_reduce(
            $matcher2Matches,
            function($expectations, $matches) use ($message) {
                $expectations[] = ['matches', ['params' => [$message], 'result' => $matches]];
                if (!empty($matches)) {
                    $expectations[] = ['getMethod', ['result' => 'method-2']];
                    $expectations[] = ['getName', ['result' => 'name-2']];
                }
                return $expectations;
            },
            []
        );
        $methodMatcher2 = $this->newPartialMockWithExpectations(MethodMatcher::class, $expectations);

        if (empty($dispatchExpected)) {
            $expectations = [['dispatch', 'never']];
        } else {
            $expectations = array_reduce(
                $dispatchExpected,
                function ($expectations, $dispatch) use ($message) {
                    list($method, $matches) = $dispatch;
                    $expectations[] = ['dispatch', ['params' => [$method, $message, $matches]]];
                    return $expectations;
                },
                []
            );
        }
        /** @var TestPlugin $plugin */
        $plugin = $this->newPartialMockWithExpectations(TestPlugin::class, $expectations);
        $plugin->setPluginMatcher($pluginMatcher);
        $plugin->setMethodMatchers([$methodMatcher1,$methodMatcher2]);

        $plugin->handle($message);
    }
}