<?php

namespace Bot;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\SlackClient;
use PHPUnit\Framework\TestCase;
use Slack\ApiClient;
use Slack\Channel;
use Slack\User;

class MessageTest extends TestCase
{
    use MockWithExpectationsTrait;

    protected $authedUser;
    protected $user;
    protected $channel;
    protected $data;

    protected function setUp()
    {
        $this->data = [
            'text' => 'this is a test',
            'user' => 'U0290RGRD',
            'channel' => 'C029E9SF9',
            'ts' => '1493671362.792940',
        ];

        $this->authedUser = $this->newPartialMockWithExpectations(User::class, [
            'getId' => ['invoked' => 'any', 'result' => 'U0AUTHED0'],
        ]);
        $this->user = $this->newPartialMockWithExpectations(User::class, [
            'getUsername' => ['invoked' => 'any', 'result' => 'user-name'],
            'getId' => ['invoked' => 'any', 'result' => 'U00USER00'],
        ]);
        $this->channel = $this->newPartialMockWithExpectations(Channel::class, [
            'getName' => ['invoked' => 'any', 'result' => 'channel-name'],
        ]);
    }

    private function newSlackClient(array $extraExpectations = []) : SlackClient
    {
        $expectations = array_merge([
            'userById' => ['invoked' => 'any', 'params' => [$this->data['user']], 'result' => $this->user],
            'channelById' => ['invoked' => 'any', 'params' => [$this->data['channel']], 'result' => $this->channel],
            'getAuthedUser' => ['invoked' => 'any', 'result' => $this->authedUser],
            'userByName' => ['invoked' => 'any', 'params' => ['user-name'], 'result' => $this->user],
        ], $extraExpectations);

        return $this->newPartialMockWithExpectations(SlackCLient::class, $expectations);
    }

    public function testGetText()
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertSame('this is a test', $message->getText());
    }

    public function testGetChannel()
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertSame($this->channel, $message->getChannel());
    }

    public function testGetUser()
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertSame($this->user, $message->getUser());
    }

    public function testGetThreadTsNewThread()
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertSame('1493671362.792940', $message->getThreadTs());
    }

    public function testGetThreadTsExistingThread()
    {
        $message = new Message($this->newSlackClient(), array_merge($this->data, ['thread_ts' => '1493670000.000000']));

        $this->assertSame('1493670000.000000', $message->getThreadTs());
    }

    public function testGetIsSelf()
    {
        $slackClient = $this->newSlackClient([
            'userById' => ['invoked' => 'any', 'params' => [$this->data['user']], 'result' => $this->authedUser],
        ]);

        $message = new Message($slackClient, $this->data);

        $this->assertTrue($message->isSelf());
    }

    public function testGetIsNotSelf()
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertFalse($message->isSelf());
    }

    public function botDataProvider() : array
    {
        return [
            [null, false],
            [false, false],
            [true, true],
        ];
    }

    /**
     * @dataProvider botDataProvider
     */
    public function testIsBot($isBot, $expected)
    {
        $apiClient = $this->createMock(ApiClient::class);
        $data = $isBot !== null ? ['is_bot' => $isBot] : [];

        $user = new User($apiClient, $data);

        $slackClient = $this->newSlackClient([
            'userById' => ['invoked' => 'any', 'params' => [$this->data['user']], 'result' => $user],
        ]);

        $message = new Message($slackClient, $this->data);

        $this->assertSame($expected, $message->isBot());
    }

    public function testHasNoAttachments()
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertFalse($message->hasAttachments());
    }

    public function testHasAttachments()
    {
        $message = new Message($this->newSlackClient(), array_merge($this->data, ['attachments' => ['123']]));

        $this->assertTrue($message->hasAttachments());
    }

    public function testGetNoAttachments()
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertEquals([], $message->getAttachments());
    }

    public function testGetAttachments()
    {
        $message = new Message($this->newSlackClient(), array_merge($this->data, ['attachments' => ['123']]));

        $this->assertEquals(['123'], $message->getAttachments());
    }

    public function testSay()
    {
        $channel = $this->createMock(Channel::class);
        $extra = ['extra' => 'param'];

        $slackClient = $this->newSlackClient([
            'say' => ['params' => ['hello', $channel, $extra]],
        ]);

        $message = new Message($slackClient, $this->data);

        $message->say('hello', $channel, $extra);
    }

    public function testReply()
    {
        $extra = ['extra' => 'param'];

        $slackClient = $this->newSlackClient([
            'say' => ['params' => ['hello', $this->channel, $extra]],
        ]);

        $message = new Message($slackClient, $this->data);

        $message->reply('hello', $extra);
    }

    public function testThread()
    {
        $extra = [
            'thread_ts' => $this->data['ts'],
            'extra' => 'param',
        ];

        $slackClient = $this->newSlackClient([
            'say' => ['params' => ['hello', $this->channel, $extra]],
        ]);

        $message = new Message($slackClient, $this->data);

        $message->thread('hello', ['extra' => 'param']);
    }

    public function testHandled()
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertFalse($message->isHandled());

        $message->setHandled(true);

        $this->assertTrue($message->isHandled());
    }

    public function testGetUsername()
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertSame('user-name', $message->getUsername());
    }

    public function testGetChannelName()
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertSame('channel-name', $message->getChannelName());
    }

    public function prefixDataProvider() : array
    {
        return [
            ['', 'this is a test', ['this is a test', 'this is a test']],
            ['hey', 'this is a test', []],
            ['hey', 'not hey this is a test', []],
            ['hey', 'hey this is a test', ['hey this is a test', 'this is a test']],
            [Message::AUTHED_USER, 'this is a test', []],
            [Message::AUTHED_USER, '<@U00USER00> this is a test', []],
            [Message::AUTHED_USER, '<@U0AUTHED0> this is a test', ['<@U0AUTHED0> this is a test', 'this is a test']],
            ['@user-name', 'this is a test', []],
            ['@user-name', '<@U0AUTHED0> this is a test', []],
            ['@user-name', '<@U00USER00> this is a test', ['<@U00USER00> this is a test', 'this is a test']],
        ];
    }

    /**
     * @dataProvider prefixDataProvider
     */
    public function testMatchesPrefix(string $prefix, string $text, $expected)
    {
        $message = new Message($this->newSlackClient(), array_merge($this->data, ['text' => $text]));

        $matches = $message->matchesPrefix($prefix);

        $this->assertSame($expected, $matches);
    }

    public function matchesIsBotDataProvider() : array
    {
        $data = [
            [null, null, true],
            [false, null, true],
            [true, null, true],
            [null, false, true],
            [false, false, true],
            [true, false, false],
            [null, true, false],
            [false, true, false],
            [true, true, true],
        ];

        return array_slice($data, 0, 100);
    }

    /**
     * @dataProvider matchesIsBotDataProvider
     */
    public function testMatchesIsBot($userIsBot, $isBot, bool $expected)
    {
        $apiClient = $this->createMock(ApiClient::class);
        $data = $userIsBot !== null ? ['is_bot' => $userIsBot] : [];

        $user = new User($apiClient, $data);

        $slackClient = $this->newSlackClient([
            'userById' => ['invoked' => 'any', 'params' => [$this->data['user']], 'result' => $user],
        ]);

        $message = new Message($slackClient, $this->data);

        $this->assertSame($expected, $message->matchesIsBot($isBot));
    }

    public function matchesChannelDataProvider() : array
    {
        $data = [
            ['', true],
            [[], true],
            ['no-match', false],
            ['channel-name', true],
            [['no-match', 'no-match-2'], false],
            [['no-match', 'channel-name'], true],
        ];

        return array_slice($data, 0, 100);
    }

    /**
     * @dataProvider matchesChannelDataProvider
     */
    public function testMatchesChannel($name, bool $expected)
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertSame($expected, $message->matchesChannel($name));
    }

    public function matchesUserDataProvider() : array
    {
        $data = [
            ['', true],
            [[], true],
            ['no-match', false],
            ['user-name', true],
            [['no-match', 'no-match-2'], false],
            [['no-match', 'user-name'], true],
        ];

        return array_slice($data, 0, 100);
    }

    /**
     * @dataProvider matchesUserDataProvider
     */
    public function testMatchesUser($name, bool $expected)
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertSame($expected, $message->matchesUser($name));
    }

    public function matchesPatternsDataProvider() : array
    {
        $data = [
            [['/no-match/'], []],
            [['/^this/'], ['this']],
            [['/^this (is) (a) (test)/'], ['this is a test', 'is', 'a', 'test']],
            [['/no-match/', '/^this/'], ['this']],
            [['/^this/', '/^this (is) (a) (test)/'], ['this']],
        ];

        return array_slice($data, 0, 100);
    }

    /**
     * @dataProvider matchesPatternsDataProvider
     */
    public function testMatchesPatterns(array $patterns, array $expected)
    {
        $message = new Message($this->newSlackClient(), $this->data);

        $this->assertSame($expected, $message->matchPatterns($patterns, 'this is a test'));
    }
}