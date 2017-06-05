<?php

namespace Plugin;


use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\MethodMatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MethodMatcherTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function matchesDataProvider() : array
    {
        $data = [
            [
                'No constraints',
                [null, [], [], []],
                [false, 'general', null, ''],
                ['formatted text'],
            ],
            [
                'isBot match true === true',
                [true, [], [], []],
                [true, 'general', null, ''],
                ['formatted text'],
            ],
            [
                'isBot match false === false',
                [false, [], [], []],
                [false, 'general', null, ''],
                ['formatted text'],
            ],
            [
                'channels match',
                [null, ['back','general'], [], []],
                [false, 'general', null, ''],
                ['formatted text'],
            ],
            [
                'users match',
                [null, [], ['alice','bob'], []],
                [false, 'general', 'bob', ''],
                ['formatted text'],
            ],
            [
                'patterns match',
                [null, [], [], ["/don't match (me)/",'/match (me)/']],
                [false, 'general', null, 'match me'],
                ['match me', 'me'],
            ],

            [
                'isBot match failed true !== false',
                [true, [], [], []],
                [false, 'general', null, ''],
                [],
            ],
            [
                'isBot match failed false !== true',
                [false, [], [], []],
                [true, 'general', null, ''],
                [],
            ],
            [
                'channels match failed',
                [null, ['legal','back'], [], []],
                [false, 'general', null, ''],
                [],
            ],
            [
                'users match failed',
                [null, [], ['alice'], []],
                [false, 'general', null, 'bob'],
                [],
            ],
            [
                "patterns don't match",
                [null, [], [], ["/don't match (me)/",'/match (me)/']],
                [false, 'general', null, "don't match this"],
                [],
            ],
        ];

        return array_slice($data, 0, 100);
    }

    private function buildPluginMatcher(array $matcherData) : MethodMatcher
    {
        list($isBot, $channels, $users, $patterns) = $matcherData;

        return new MethodMatcher('name', $isBot, $channels, $users, $patterns, 'method-name', $this->createMock(LoggerInterface::class));
    }

    private function buildMessage(array $messageData) : Message
    {
        list($isBot, $channelName, $username, $pluginText) = $messageData;

        /** @var Message $message */
        $message = $this->newPartialMockWithExpectations(Message::class, [
            'isBot' => ['invoked' => 'any', 'result' => $isBot],
            'getChannelName' => ['invoked' => 'any', 'result' => $channelName],
            'getUsername' => ['invoked' => 'any', 'result' => $username],
            'getPluginText' => ['invoked' => 'any', 'result' => $pluginText],
            'getFormattedText' => ['invoked' => 'any', 'result' => 'formatted text'],
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

    public function testGetName()
    {
        $matcher = new MethodMatcher('name', false, [], [], [], 'method-name', $this->createMock(LoggerInterface::class));

        $this->assertEquals('name', $matcher->getName());
    }

    public function testGetMethod()
    {
        $matcher = new MethodMatcher('name', false, [], [], [], 'method-name', $this->createMock(LoggerInterface::class));

        $this->assertEquals('method-name', $matcher->getMethod());
    }
}