<?php

namespace Nopolabs\Yabot;


use Evenement\EventEmitterTrait;
use GuzzleHttp\Client;
use Noodlehaus\Config;
use Nopolabs\Yabot\Plugins\PluginInterface;
use Nopolabs\Yabot\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Slack\ChannelInterface;
use Slack\Payload;
use Slack\RealTimeClient;
use Slack\User;

class Yabot
{
    use EventEmitterTrait;

    /** @var Config */
    protected $config;

    /** @var StorageInterface */
    protected $storage;

    /** @var LoggerInterface */
    protected $logger;

    /** @var LoopInterface */
    protected $loop;

    /** @var RealTimeClient */
    protected $client;

    /** @var Client */
    protected $guzzle;

    /** @var Users */
    protected $users;

    /** @var Channels */
    protected $channels;

    /** @var User */
    protected $authedUser;

    public function __construct(
        Config $config,
        StorageInterface $storage,
        LoggerInterface $logger,
        LoopInterface $loop,
        RealTimeClient $client,
        Client $guzzle
    ) {
        $this->config = $config;
        $this->storage = $storage;
        $this->logger = $logger;
        $this->loop = $loop;
        $this->client = $client;
        $this->guzzle = $guzzle;

        $this->users = new Users();
        $this->channels = new Channels();
    }

    public function run()
    {
        $this->client->on('message', function (Payload $data) {
            $message = new Message($this, $data->getData());

            $this->getLog()->info('Noticed message', [
                'text' => $message->getText(),
            ]);

            $this->emit('message', [$message]);
        });

        $this->initChannelUpdateHandlers();
        $this->initUserUpdateHandlers();

        $this->client->connect()->then(function () {
            $this->updateAuthedUser();
            $this->updateUsers();
            $this->updateChannels();
        });

        $this->loop->run();
    }

    public function quit()
    {
        $this->getLog()->info('Quitting');
        $this->client->disconnect();
    }

    public function say($text, ChannelInterface $channel)
    {
        $this->getLog()->info("say: $text");
        $this->client->send($text, $channel);
    }

    public function getConfig() : Config
    {
        return $this->config;
    }

    public function getStorage() : StorageInterface
    {
        return $this->storage;
    }

    public function getLog() : LoggerInterface
    {
        return $this->logger;
    }

    public function getLoop() : LoopInterface
    {
        return $this->loop;
    }

    public function getClient() : RealTimeClient
    {
        return $this->client;
    }

    public function getGuzzle() : Client
    {
        return $this->guzzle;
    }

    public function getUsers() : Users
    {
        return $this->users;
    }

    public function getChannels() : Channels
    {
        return $this->channels;
    }

    protected function updateAuthedUser()
    {
        $this->client->getAuthedUser()->then(function (User $user) {
            $this->authedUser = $user;
        });
    }

    public function updateUsers()
    {
        $this->client->getUsers()->then(function(array $users) {
            $this->users->update($users);
        });
    }

    public function updateChannels()
    {
        $this->client->getChannels()->then(function(array $channels) {
            $this->channels->update($channels);
        });
    }

    protected function initChannelUpdateHandlers()
    {
        $events = ['channel_created', 'channel_deleted', 'channel_rename'];
        foreach ($events as $event) {
            $this->client->on($event, [$this, 'updateChannels']);
        }
    }

    protected function initUserUpdateHandlers()
    {
        $events = ['user_change'];
        foreach ($events as $event) {
            $this->client->on($event, [$this, 'updateUsers']);
        }
    }

    public function addPlugin(PluginInterface $plugin)
    {
        $this->on('message', [$plugin, 'onMessage']);
    }
}
