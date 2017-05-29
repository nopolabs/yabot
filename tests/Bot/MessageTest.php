<?php

namespace Nopolabs\Yabot\Tests\Bot;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Bot\Channels;
use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\SlackClient;
use Nopolabs\Yabot\Bot\Users;
use PHPUnit\Framework\TestCase;
use Slack\ApiClient;
use Slack\Channel;
use Slack\RealTimeClient;
use Slack\User;

class MessageTest extends TestCase
{
    use MockWithExpectationsTrait;

    protected $authedUser;
    protected $user;
    protected $groot;
    protected $channel;
    protected $data;
    protected $realTimeClient;
    protected $users;
    protected $channels;

    protected function setUp()
    {
        $this->data = [
            'text' => 'this is a test',
            'user' => 'U00USER00',
            'channel' => 'C00CHAN00',
            'ts' => '1493671362.792940',
        ];

        $this->authedUser = $this->newPartialMockWithExpectations(User::class, [
            'getUsername' => ['invoked' => 'any', 'result' => 'authed-user'],
            'getId' => ['invoked' => 'any', 'result' => 'U0AUTHED0'],
        ]);
        $this->user = $this->newPartialMockWithExpectations(User::class, [
            'getUsername' => ['invoked' => 'any', 'result' => 'user-name'],
            'getId' => ['invoked' => 'any', 'result' => 'U00USER00'],
        ]);
        $this->groot = $this->newPartialMockWithExpectations(User::class, [
            'getUsername' => ['invoked' => 'any', 'result' => 'groot'],
            'getId' => ['invoked' => 'any', 'result' => 'U00GROOT0'],
        ]);
        $this->channel = $this->newPartialMockWithExpectations(Channel::class, [
            'getName' => ['invoked' => 'any', 'result' => 'channel-name'],
            'getId' => ['invoked' => 'any', 'result' => 'C00CHAN00'],
        ]);

        $this->users = new Users();
        $this->users->update([$this->authedUser, $this->user, $this->groot]);

        $this->channels = new Channels();
        $this->channels->update([$this->channel]);

        $this->realTimeClient = $this->createMock(RealTimeClient::class);
    }

    private function newSlackClient(array $newExpectations = []) : SlackClient
    {
        $expectations = array_merge([
            'getAuthedUser' => ['invoked' => 'any', 'result' => $this->authedUser],
        ], $newExpectations);

        return $this->newPartialMockWithExpectations(
            SlackCLient::class,
            $expectations,
            [$this->realTimeClient, $this->users, $this->channels]
        );
    }

    public function testGetText()
    {
        $data = array_merge($this->data, ['text' => 'I am <@U00GROOT0>']);

        $message = new Message($this->newSlackClient(), $data);

        $this->assertSame('I am <@U00GROOT0>', $message->getText());
    }

    public function testGetFormattedText()
    {
        $data = array_merge($this->data, ['text' => 'I am <@U00GROOT0>']);

        $message = new Message($this->newSlackClient(), $data);

        $this->assertSame('I am @groot', $message->getFormattedText());
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
        $data = array_merge($this->data, ['user' => 'U0AUTHED0']);

        $message = new Message($this->newSlackClient(), $data);

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
        $data = [
            ['', 'this is a test', ['this is a test', 'this is a test']],
            ['hey', 'this is a test', []],
            ['hey', 'not hey this is a test', []],
            ['hey', 'hey this is a test', ['hey this is a test', 'this is a test']],
            [Message::AUTHED_USER, 'this is a test', []],
            [Message::AUTHED_USER, '<@U00USER00> this is a test', []],
            [Message::AUTHED_USER, '<@U0AUTHED0> this is a test', ['@authed-user this is a test', 'this is a test']],
            ['@user-name', 'this is a test', []],
            ['@user-name', '<@U0AUTHED0> this is a test', []],
            ['@user-name', '<@U00USER00> this is a test', ['@user-name this is a test', 'this is a test']],
        ];

        return array_slice($data, 0, 100);
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

        $message->setPluginText('this is a test');

        $this->assertSame($expected, $message->matchPatterns($patterns));
    }
}