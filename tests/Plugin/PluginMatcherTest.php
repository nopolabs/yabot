<?php

namespace Plugin;


use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginMatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PluginMatcherTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function matchesDataProvider() : array
    {
        $data = [
            [
                'No constraints',
                [null, [], []],
                [false, 'general', null, false],
                true,
            ],
            [
                'isBot match true === true',
                [true, [], []],
                [true, 'general', null, false],
                true,
            ],
            [
                'isBot match false === false',
                [false, [], []],
                [false, 'general', null, false],
                true,
            ],
            [
                'channels match',
                [null, ['back','general'], []],
                [false, 'general', null, false],
                true,
            ],
            [
                'users match',
                [null, [], ['alice','bob']],
                [false, 'general', 'bob', false],
                true,
            ],

            [
                'message already handled',
                [null, [], []],
                [false, 'general', null, true],
                false,
            ],
            [
                'isBot match failed true !== false',
                [true, [], []],
                [false, 'general', null, false],
                false,
            ],
            [
                'isBot match failed false !== true',
                [false, [], []],
                [true, 'general', null, false],
                false,
            ],
            [
                'channels match failed',
                [null, ['legal','back'], []],
                [false, 'general', null, false],
                false,
            ],
            [
                'users match failed',
                [null, [], ['alice']],
                [false, 'general', 'bob', false],
                false,
            ],
        ];

        return array_slice($data, 0, 100);
    }

    private function buildPluginMatcher(array $matcherData) : PluginMatcher
    {
        list($isBot, $channels, $users) = $matcherData;

        return new PluginMatcher('plugin-id', $isBot, $channels, $users, $this->createMock(LoggerInterface::class));
    }

    private function buildMessage(array $messageData) : Message
    {
        list($isBot, $channelName, $username, $isHandled) = $messageData;

        /** @var Message $message */
        $message = $this->newPartialMockWithExpectations(Message::class, [
            'isHandled' => ['invoked' => 'any', 'result' => $isHandled],
            'isBot' => ['invoked' => 'any', 'result' => $isBot],
            'getChannelName' => ['invoked' => 'any', 'result' => $channelName],
            'getUsername' => ['invoked' => 'any', 'result' => $username],
        ]);

        return $message;
    }

    /**
     * @dataProvider matchesDataProvider
     */
    public function testMatches($case, array $matcherData, array $messageData, $expected)
    {
        $pluginMatcher = $this->buildPluginMatcher($matcherData);

        $message = $this->buildMessage($messageData);

        $actual = $pluginMatcher->matches($message);

        $this->assertEquals($expected, $actual);
    }
}