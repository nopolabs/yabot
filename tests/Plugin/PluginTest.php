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
            [[false, [], []]],
            [[true, [], []]],
            [[true, ['one'], []], ['method-1', ['one']]],
            [[true, [], ['two']], ['method-2', ['two']]],
        ];
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(array $match, $expected = [])
    {
        list($matchPlugin, $matchMethod1, $matchMethod2) = $match;

        $message = $this->createMock(Message::class);

        $pluginMatcher = $this->newPartialMockWithExpectations(PluginMatcher::class, [
            'matches' => ['invoked' => 'any', 'params' => [$message], 'result' => $matchPlugin],
        ]);

        $methodMatcher1 = $this->newPartialMockWithExpectations(MethodMatcher::class, [
            'matches' => ['invoked' => 'any', 'params' => [$message], 'result' => $matchMethod1],
            'getMethod' => ['invoked' => 'any', 'result' => 'method-1'],
            'getName' => ['invoked' => 'any', 'result' => 'name-1'],
        ]);

        $methodMatcher2 = $this->newPartialMockWithExpectations(MethodMatcher::class, [
            'matches' => ['invoked' => 'any', 'params' => [$message], 'result' => $matchMethod2],
            'getMethod' => ['invoked' => 'any', 'result' => 'method-2'],
            'getName' => ['invoked' => 'any', 'result' => 'name-2'],
        ]);

        if (empty($expected)) {
            $dispatch = ['invoked' => 'never'];
        } else {
            list($method, $matches) = $expected;
            $dispatch = ['params' => [$method, $message, $matches]];
        }

        /** @var TestPlugin $plugin */
        $plugin = $this->newPartialMockWithExpectations(TestPlugin::class, [
            'getPluginMatcher' => ['invoked' => 'any', 'result' => $pluginMatcher],
            'getMethodMatchers' => ['invoked' => 'any', 'result' => [$methodMatcher1,$methodMatcher2]],
            'dispatch' => $dispatch,
        ]);

        $plugin->handle($message);
    }
}