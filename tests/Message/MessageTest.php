<?php

namespace Nopolabs\Yabot\Tests\Message;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Slack\Channels;
use Nopolabs\Yabot\Slack\Client;
use Nopolabs\Yabot\Slack\Users;
use Nopolabs\Yabot\Yabot;
use PHPUnit\Framework\TestCase;
use Slack\ApiClient;
use Slack\Channel;
use Slack\RealTimeClient;
use Slack\User;

class MessageTest extends TestCase
{
    use MockWithExpectationsTrait;

    private $slackClient;
    private $authedUser;
    private $user;
    private $channel;
    private $data;
    private $realTimeClient;
    private $users;
    private $channels;

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
        $this->channel = $this->newPartialMockWithExpectations(Channel::class, [
            'getName' => ['invoked' => 'any', 'result' => 'channel-name'],
            'getId' => ['invoked' => 'any', 'result' => 'C00CHAN00'],
        ]);

        $this->slackClient = $this->newPartialMockWithExpectations(Client::class, [
            'getAuthedUser' => ['invoked' => 'any', 'result' => $this->authedUser],
        ]);

        $this->users = new Users();
        $this->users->update([$this->authedUser, $this->user]);

        $this->channels = new Channels();
        $this->channels->update([$this->channel]);

        $this->realTimeClient = $this->createMock(RealTimeClient::class);
    }

    public function testGetData()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $this->assertSame($this->data, $message->getData());
    }

    public function testGetText()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $this->assertSame('this is a test', $message->getText());
    }

    public function testPluginText()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $this->assertNull($message->getPluginText());

        $message->setPluginText('plugin text');

        $this->assertEquals('plugin text', $message->getPluginText());
    }

    public function testGetChannel()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $this->assertSame($this->channel, $message->getChannel());
    }

    public function testGetUser()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $this->assertSame($this->user, $message->getUser());
    }

    public function testGetThreadTsNewThread()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $this->assertSame('1493671362.792940', $message->getThreadTs());
    }

    public function testGetThreadTsExistingThread()
    {
        $data = array_merge($this->data, ['thread_ts' => '1493670000.000000']);

        $message = new Message($this->slackClient, $data, 'formatted-text', $this->user, $this->channel);

        $this->assertSame('1493670000.000000', $message->getThreadTs());
    }

    public function testGetIsSelf()
    {
        $data = array_merge($this->data, ['user' => 'U0AUTHED0']);

        $message = new Message($this->slackClient, $data, 'formatted-text', $this->authedUser, $this->channel);

        $this->assertTrue($message->isSelf());
    }

    public function testGetIsNotSelf()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

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
        $data = $isBot ? ['bot_id' => 'B00000001'] : [];
        $data = array_merge($this->data, $data);

        $message = new Message($this->slackClient, $data, 'formatted-text', $this->user, $this->channel);

        $this->assertSame($expected, $message->isBot());
    }

    public function testHasNoAttachments()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $this->assertFalse($message->hasAttachments());
    }

    public function testHasAttachments()
    {
        $data = array_merge($this->data, ['attachments' => ['123']]);

        $message = new Message($this->slackClient, $data, 'formatted-text', $this->user, $this->channel);

        $this->assertTrue($message->hasAttachments());
    }

    public function testGetNoAttachments()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $this->assertEquals([], $message->getAttachments());
    }

    public function testGetAttachments()
    {
        $data = array_merge($this->data, ['attachments' => ['123']]);

        $message = new Message($this->slackClient, $data, 'formatted-text', $this->user, $this->channel);

        $this->assertEquals(['123'], $message->getAttachments());
    }

    public function testSay()
    {
        $channel = $this->createMock(Channel::class);
        $extra = ['extra' => 'param'];

        $slackClient = $this->newPartialMockWithExpectations(Client::class, [
            'say' => ['params' => ['hello', $channel, $extra]],
        ]);

        $message = new Message($slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $message->say('hello', $channel, $extra);
    }

    public function testReply()
    {
        $extra = ['extra' => 'param'];

        $slackClient = $this->newPartialMockWithExpectations(Client::class, [
            'say' => ['params' => ['hello', $this->channel, $extra]],
        ]);

        $message = new Message($slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $message->reply('hello', $extra);
    }

    public function testThread()
    {
        $extra = [
            'thread_ts' => $this->data['ts'],
            'extra' => 'param',
        ];

        $slackClient = $this->newPartialMockWithExpectations(Client::class, [
            'say' => ['params' => ['hello', $this->channel, $extra]],
        ]);

        $message = new Message($slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $message->thread('hello', ['extra' => 'param']);
    }

    public function testHandled()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $this->assertFalse($message->isHandled());

        $message->setHandled(true);

        $this->assertTrue($message->isHandled());
    }

    public function testGetUsername()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $this->assertSame('user-name', $message->getUsername());
    }

    public function testGetChannelName()
    {
        $message = new Message($this->slackClient, $this->data, 'formatted-text', $this->user, $this->channel);

        $this->assertSame('channel-name', $message->getChannelName());
    }
}