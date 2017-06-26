<?php
namespace Nopolabs\Yabot\Tests\Plugin;

use Exception;
use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PluginManagerTest extends TestCase
{
    use MockWithExpectationsTrait;

    private $logger;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testLoadPlugin()
    {
        $pluginMap = [];

        $pluginsData = [
            ['plugin-1', 100, '<none>'],
            ['plugin-2', 100, '<authed_user>'],
            ['plugin-3', 200, '<authed_user>'],
            ['plugin-4', 50, '<authed_user>'],
            ['plugin-5', 150, '<none>'],
            ['plugin-6', 100, '<none>'],
        ];

        foreach ($pluginsData as $data) {
            list($pluginId, $priority, $prefix) = $data;

            /** @var PluginInterface $plugin */
            $plugin = $this->newPartialMockWithExpectations(PluginInterface::class, [
                ['getPriority', ['result' => $priority]],
                ['getPrefix', ['result' => $prefix]],
            ]);

            $pluginMap[$pluginId] = $plugin;
        }

        $priorityMap = [
            200 => [
                '<authed_user>' => [
                    'plugin-3' => $pluginMap['plugin-3'],
                ],
            ],
            150 => [
                '<none>' => [
                    'plugin-5' => $pluginMap['plugin-5'],
                ],
            ],
            100 => [
                '<none>' => [
                    'plugin-1' => $pluginMap['plugin-1'],
                    'plugin-6' => $pluginMap['plugin-6'],
                ],
                '<authed_user>' => [
                    'plugin-2' => $pluginMap['plugin-2'],
                ],
            ],
            50 => [
                '<authed_user>' => [
                    'plugin-4' => $pluginMap['plugin-4'],
                ],
            ],
        ];

        $manager = new TestPluginManager($this->logger);

        foreach ($pluginMap as $pluginId => $plugin) {
            $manager->loadPlugin($pluginId, $plugin);
        }

        $this->assertEquals($pluginMap, $manager->getPluginMap());
        $this->assertEquals($priorityMap, $manager->getPriorityMap());
    }

    public function testGetHelp()
    {
        $expected = [
            'test.plugin',
            '    line-1',
            '    line-2',
        ];

        $pluginMap = [
            'test.plugin' => $this->newPartialMockWithExpectations(PluginInterface::class, [
                ['help', ['result' => "line-1\nline-2\n"]],
                ['getPrefix', ['result' => "please"]],
            ]),
        ];

        /** @var PluginManager $manager */
        $manager = $this->newPartialMockWithExpectations(PluginManager::class, [
            ['getPluginMap', ['result' => $pluginMap]],
        ]);

        $actual = $manager->getHelp();

        $this->assertEquals($expected, $actual);
    }

    public function testGetStatuses()
    {
        $expected = [
            'There are 1 plugins loaded.',
            "test.plugin I'm good",
        ];

        $pluginMap = [
            'test.plugin' => $this->newPartialMockWithExpectations(PluginInterface::class, [
                ['status', ['result' => "I'm good"]],
            ]),
        ];

        /** @var PluginManager $manager */
        $manager = $this->newPartialMockWithExpectations(PluginManager::class, [
            'getPluginMap' => ['invoked' => 'any', 'result' => $pluginMap],
        ]);

        $actual = $manager->getStatuses();

        $this->assertEquals($expected, $actual);
    }

    public function testUpdatePrefixes()
    {
        $beforeMap = [
            1 => ['<authed_user>' => ['test-1']],
            2 => ['yabot' => ['test-2']],
        ];
        $afterMap = [
            1 => ['@yabot-user-name' => ['test-1']],
            2 => ['yabot' => ['test-2']],
        ];

        $manager = new TestPluginManager($this->logger);

        $manager->setPriorityMap($beforeMap);

        $manager->updatePrefixes('yabot-user-name');

        $this->assertEquals($afterMap, $manager->getPriorityMap());
    }

    public function matchesPrefixDataProvider()
    {
        return [
            ['<none>', 'test text', ['test text', 'test text']],
            ['test', 'test text', ['test text', 'text']],
            ['test', 'no-match text', []],
        ];
    }

    /**
     * @dataProvider matchesPrefixDataProvider
     */
    public function testMatchesPrefix($prefix, $text, $expected)
    {
        $manager = new PluginManager($this->logger);

        $matches = $manager->matchesPrefix($prefix, $text);

        $this-> assertEquals($expected, $matches);
    }

    public function testDispatchMessage()
    {
        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['getformattedText', ['result' => 'prefix plugin-text']],
            ['setPluginText', ['params' => ['plugin-text']]],
            ['getData', ['result' => ['data']]],
            ['isHandled', ['result' => false]],
            ['isHandled', ['result' => true]],
        ]);

        $plugin1 = $this->newPartialMockWithExpectations(PluginInterface::class, [
            ['handle', 'never'],
        ]);

        $plugin2 = $this->newPartialMockWithExpectations(PluginInterface::class, [
            ['handle', ['params' => [$message], 'throws' => new Exception('boom!')]],
        ]);

        $plugin3 = $this->newPartialMockWithExpectations(PluginInterface::class, [
            ['handle', ['params' => [$message]]],
        ]);

        $expected = '/^Unhandled Exception in plugin-id2/';

        $this->setExpectation($this->logger, 'warning', [
            'params' => [$this->matchesRegularExpression($expected), []],
        ]);

        $priorityMap = [
            1 => [
                'not-prefix' => [
                    'plugin-id1' => $plugin1,
                ],
                'prefix' => [
                    'plugin-id2' => $plugin2,
                    'plugin-id3' => $plugin3,
                ],
            ],
        ];

        $manager = new TestPluginManager($this->logger);

        $manager->setPriorityMap($priorityMap);

        $manager->dispatchMessage($message);
    }
}