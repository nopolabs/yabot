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
                [false, 'general', null, '', false],
                true,
            ],
            [
                'isBot match true === true',
                [true, [], [], []],
                [true, 'general', null, '', false],
                true,
            ],
            [
                'isBot match false === false',
                [false, [], [], []],
                [false, 'general', null, '', false],
                true,
            ],
            [
                'channels match',
                [null, ['back','general'], [], []],
                [false, 'general', null, '', false],
                true,
            ],
            [
                'users match',
                [null, [], ['alice','bob'], []],
                [false, 'general', 'bob', '', false],
                true,
            ],
            [
                'patterns match',
                [null, [], [], ["/don't match (me)/",'/match (me)/']],
                [false, 'general', null, 'match me', false],
                ['match me', 'me'],
            ],

            [
                'message already handled',
                [null, [], [], []],
                [false, 'general', null, '', true],
                false,
            ],
            [
                'isBot match failed true !== false',
                [true, [], [], []],
                [false, 'general', null, '', false],
                false,
            ],
            [
                'isBot match failed false !== true',
                [false, [], [], []],
                [true, 'general', null, '', false],
                false,
            ],
            [
                'channels match failed',
                [null, ['legal','back'], [], []],
                [false, 'general', null, '', false],
                false,
            ],
            [
                'users match failed',
                [null, [], ['alice'], []],
                [false, 'general', null, 'bob', false],
                false,
            ],
            [
                "patterns don't match",
                [null, [], [], ["/don't match (me)/",'/match (me)/']],
                [false, 'general', null, "don't match this", false],
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
        list($isBot, $channelName, $username, $pluginText, $isHandled) = $messageData;

        /** @var Message $message */
        $message = $this->newPartialMockWithExpectations(Message::class, [
            'isHandled' => ['invoked' => 'any', 'result' => $isHandled],
            'isBot' => ['invoked' => 'any', 'result' => $isBot],
            'getChannelName' => ['invoked' => 'any', 'result' => $channelName],
            'getUsername' => ['invoked' => 'any', 'result' => $username],
            'getPluginText' => ['invoked' => 'any', 'result' => $pluginText],
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