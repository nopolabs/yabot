<?php

namespace Slack;


use Closure;
use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Slack\Channels;
use Nopolabs\Yabot\Slack\Client;
use Nopolabs\Yabot\Slack\Users;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

class ClientTest extends TestCase
{
    use MockWithExpectationsTrait;

    private $realTimeClient;
    private $channels;
    private $users;
    private $logger;

    protected function setUp()
    {
        $this->realTimeClient = $this->createMock(RealTimeClient::class);
        $this->channels = $this->createMock(Channels::class);
        $this->users = $this->createMock(Users::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testGetRealTimeClient()
    {
        $client = new Client($this->realTimeClient, $this->users, $this->channels, [], $this->logger);

        $this->assertSame($this->realTimeClient, $client->getRealTimeClient());
    }

    public function testInit()
    {
        $client = new Client($this->realTimeClient, $this->users, $this->channels, [], $this->logger);

        $this->setAtExpectations($this->realTimeClient, [
            ['on', ['params' => ['channel_created', $this->isInstanceOf(Closure::class)]]],
            ['on', ['params' => ['channel_deleted', $this->isInstanceOf(Closure::class)]]],
            ['on', ['params' => ['channel_rename', $this->isInstanceOf(Closure::class)]]],
            ['on', ['params' => ['user_change', $this->isInstanceOf(Closure::class)]]],
        ]);

        $client->init();
    }

    public function testUpdate()
    {
        $client = new Client($this->realTimeClient, $this->users, $this->channels, [], $this->logger);

        $authedUser = $this->newPartialMockWithExpectations(User::class, [
            ['getUsername', ['result' => 'alice']],
        ]);

        $usersPromise = new FulfilledPromise(['users']);
        $channelsPromise = new FulfilledPromise(['channels']);
        $authedUserPromise = new FulfilledPromise($authedUser);

        $updatedAuthedUser = null;
        $authedUserUpdated = function(User $authedUser) use (&$updatedAuthedUser) {
            $updatedAuthedUser = $authedUser;
        };

        $this->setAtExpectations($this->realTimeClient, [
            ['getUsers', ['result' => $usersPromise]],
            ['getChannels', ['result' => $channelsPromise]],
            ['getAuthedUser', ['result' => $authedUserPromise]],
        ]);

        $this->setAtExpectations($this->users, [
            ['update', ['params' => [['users']]]],
        ]);

        $this->setAtExpectations($this->channels, [
            ['update', ['params' => [['channels']]]],
        ]);

        $this->setAtExpectations($this->users, [
            ['update', ['params' => [['users']]]],
        ]);

        $client->update($authedUserUpdated);

        $this->assertSame($authedUser, $client->getAuthedUser());
        $this->assertSame('alice', $client->getAuthedUsername());
        $this->assertSame($authedUser, $updatedAuthedUser);
    }

    public function testConnect()
    {
        $client = new Client($this->realTimeClient, $this->users, $this->channels, [], $this->logger);

        $promise = $this->createMock(PromiseInterface::class);

        $this->setAtExpectations($this->realTimeClient, [['connect', ['result' => $promise]]]);

        $actual = $client->connect();

        $this->assertSame($promise, $actual);
    }

    public function testDisconnect()
    {
        $client = new Client($this->realTimeClient, $this->users, $this->channels, [], $this->logger);

        $promise = $this->createMock(PromiseInterface::class);

        $this->setAtExpectations($this->realTimeClient, [['disconnect', ['result' => $promise]]]);

        $actual = $client->disconnect();

        $this->assertSame($promise, $actual);
    }

    public function testSayChannelNotFound()
    {
        /** @var Client $client */
        $client = $this->newPartialMockWithExpectations(Client::class, [
            ['getChannelByName', ['params' => ['unknown-channel'], 'result' => null]],
            ['getChannelById', ['params' => ['unknown-channel'], 'result' => null]],
            ['warning', ['params' => ['No channel, trying to say: what?']]],
        ]);

        $client->say('what?', 'unknown-channel');
    }

    public function testSay()
    {
        $channel = $this->createMock(ChannelInterface::class);

        /** @var Client $client */
        $client = $this->newPartialMockWithExpectations(Client::class, [
            ['post', ['params' => ['what?', $channel, ['foo' => 'bar']]]],
        ]);

        $client->say('what?', $channel, ['foo' => 'bar']);
    }

    public function testSayUsingWebSocket()
    {
        $channel = $this->createMock(ChannelInterface::class);

        /** @var Client $client */
        $client = $this->newPartialMockWithExpectations(Client::class, [
            ['useWebSocket', ['result' => true]],
            ['send', ['params' => ['what?', $channel]]],
        ]);

        $client->say('what?', $channel);
    }

    public function testSend()
    {
        $client = new Client($this->realTimeClient, $this->users, $this->channels, [], $this->logger);

        $channel = $this->createMock(ChannelInterface::class);

        $this->setAtExpectations($this->realTimeClient, [['send', ['params' => ['what?', $channel]]]]);

        $client->send('what?', $channel);
    }

    public function testPost()
    {
        $client = new Client($this->realTimeClient, $this->users, $this->channels, [], $this->logger);

        /** @var ChannelInterface $channel */
        $channel = $this->newPartialMockWithExpectations(ChannelInterface::class, [
            ['getId', ['result' => 'channel-id']],
        ]);

        $this->setAtExpectations($this->realTimeClient, [
            ['apiCall', [
                'params' => [
                    'chat.postMessage',
                    [
                        'text' => 'what?',
                        'channel' => 'channel-id',
                        'as_user' => true,
                        'foo' => 'bar',
                    ],
                ]]]]);

        $client->post('what?', $channel, ['foo' => 'bar']);
    }

    public function testGetUserById()
    {
        $client = new Client($this->realTimeClient, $this->users, $this->channels, [], $this->logger);

        $user = $this->createMock(User::class);

        $this->setAtExpectations($this->users, [
            ['byId', ['params' => ['U00000000'], 'result' => $user]],
        ]);

        $actual = $client->getUserById('U00000000');

        $this->assertSame($user, $actual);
    }

    public function testGetUserByName()
    {
        $client = new Client($this->realTimeClient, $this->users, $this->channels, [], $this->logger);

        $user = $this->createMock(User::class);

        $this->setAtExpectations($this->users, [
            ['byName', ['params' => ['user-name'], 'result' => $user]],
        ]);

        $actual = $client->getUserByName('user-name');

        $this->assertSame($user, $actual);
    }

    public function testGetChannelById()
    {
        $client = new Client($this->realTimeClient, $this->users, $this->channels, [], $this->logger);

        $channel = $this->createMock(Channel::class);

        $this->setAtExpectations($this->channels, [
            ['byId', ['params' => ['C00000000'], 'result' => $channel]],
        ]);

        $actual = $client->getChannelById('C00000000');

        $this->assertSame($channel, $actual);
    }

    public function testGetChannelByName()
    {
        $client = new Client($this->realTimeClient, $this->users, $this->channels, [], $this->logger);

        $channel = $this->createMock(Channel::class);

        $this->setAtExpectations($this->channels, [
            ['byName', ['params' => ['channel-name'], 'result' => $channel]],
        ]);

        $actual = $client->getChannelByName('channel-name');

        $this->assertSame($channel, $actual);
    }
}