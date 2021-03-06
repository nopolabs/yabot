<?php

namespace Nopolabs\Yabot\Slack;


use Closure;
use Nopolabs\Yabot\Helpers\ConfigTrait;
use Nopolabs\Yabot\Helpers\LogTrait;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use Slack\Bot;
use Slack\Channel;
use Slack\ChannelInterface;
use Slack\Payload;
use Slack\RealTimeClient;
use Slack\User;

class Client
{
    use ConfigTrait;
    use LogTrait;

    /** @var RealTimeClient */
    private $realTimeClient;

    /** @var Users */
    private $users;

    /** @var Bots */
    private $bots;

    /** @var Channels */
    private $channels;

    /** @var User */
    protected $authedUser;

    public function __construct(
        RealTimeClient $realTimeClient,
        Users $users,
        Bots $bots,
        Channels $channels,
        array $config = [],
        LoggerInterface $log = null)
    {
        $this->realTimeClient = $realTimeClient;
        $this->users = $users;
        $this->bots = $bots;
        $this->channels = $channels;
        $this->setConfig($config);
        $this->setLog($log);
    }

    public function init() : Client
    {
        $this->initChannelUpdateHandlers();
        $this->initUserUpdateHandlers();

        return $this;
    }

    public function update(Closure $authedUserUpdated)
    {
        $this->updateUsers();
        $this->updateBots();
        $this->updateChannels();
        $this->updateAuthedUser($authedUserUpdated);
    }

    public function onEvent($event, array $onEvent)
    {
        $this->realTimeClient->on($event, function(Payload $payload) use ($onEvent) {
            call_user_func($onEvent, $payload);
        });
    }

    public function getRealTimeClient()
    {
        return $this->realTimeClient;
    }

    public function getAuthedUser()
    {
        return $this->authedUser;
    }

    public function getAuthedUsername()
    {
        return $this->authedUser->getUsername();
    }

    public function connect() : PromiseInterface
    {
        return $this->realTimeClient->connect();
    }

    public function disconnect()
    {
        $this->realTimeClient->disconnect();
    }

    public function reconnect() : PromiseInterface
    {
        $this->realTimeClient->disconnect();
        return $this->realTimeClient->connect();
    }

    public function ping() : PromiseInterface
    {
        return $this->getRealTimeClient()->ping();
    }

    public function say($text, $channelOrName, array $additionalParameters = [])
    {
        $channel = $this->resolveChannel($channelOrName);

        if (!$channel) {
            $this->warning('No channel, trying to say: '.$text);
            return;
        }

        if (empty($additionalParameters) && $this->useWebSocket()) {
            // WebSocket send does not support message formatting.
            $this->send($text, $channel);
            return;
        }

        // Http post send supports message formatting.
        $this->post($text, $channel, $additionalParameters);
    }

    public function send($text, ChannelInterface $channel)
    {
        $this->realTimeClient->send($text, $channel);
    }

    public function post($text, ChannelInterface $channel, array $additionalParameters = [])
    {
        $parameters = array_merge([
            'text' => $text,
            'channel' => $channel->getId(),
            'as_user' => true,
        ], $additionalParameters);

        $this->realTimeClient->apiCall('chat.postMessage', $parameters);
    }

    public function directMessage($text, $userName)
    {
        $parameters = [
            'text' => $text,
            'channel' => $userName,
            'as_user' => false,
        ];

        $this->realTimeClient->apiCall('chat.postMessage', $parameters);
    }


    public function getUsersMap() : array
    {
        return $this->users->getMap();
    }

    public function getBotsMap() : array
    {
        return $this->bots->getMap();
    }

    public function getChannelsMap() : array
    {
        return $this->channels->getMap();
    }

    /**
     * @param $userId
     * @return null|User
     */
    public function getUserById($userId)
    {
        return $this->users->byId($userId);
    }

    /**
     * @param $name
     * @return null|User
     */
    public function getUserByName($name)
    {
        return $this->users->byName($name);
    }

    /**
     * @param $botId
     * @return null|Bot
     */
    public function getBotById($botId)
    {
        return $this->bots->byId($botId);
    }

    /**
     * @param $name
     * @return null|Bot
     */
    public function getBotByName($name)
    {
        return $this->bots->byName($name);
    }

    /**
     * @param $channelId
     * @return null|Channel
     */
    public function getChannelById($channelId)
    {
        return $this->channels->byId($channelId);
    }

    /**
     * @param $name
     * @return null|Channel
     */
    public function getChannelByName($name)
    {
        return $this->channels->byName($name);
    }

    public function updateUsers()
    {
        $this->realTimeClient->getUsers()->then(function(array $users) {
            $this->users->update($users);
        });
    }

    public function updateBots()
    {
        $this->realTimeClient->getBots()->then(function(array $bots) {
            $this->bots->update($bots);
        });
    }

    public function updateChannels()
    {
        $this->realTimeClient->getChannels()->then(function(array $channels) {
            $this->channels->update($channels);
        });
    }

    protected function updateAuthedUser(Closure $authedUserUpdated)
    {
        $this->realTimeClient->getAuthedUser()->then(function(User $user) use ($authedUserUpdated) {
            $this->authedUser = $user;
            $authedUserUpdated($user);
        });
    }

    protected function initChannelUpdateHandlers()
    {
        $events = ['channel_created', 'channel_deleted', 'channel_rename'];
        foreach ($events as $event) {
            $this->onEvent($event, [$this, 'updateChannels']);
        }
    }

    protected function initUserUpdateHandlers()
    {
        $events = ['user_change'];
        foreach ($events as $event) {
            $this->onEvent($event, [$this, 'updateUsers']);
        }
    }

    protected function useWebSocket() : bool
    {
        return (bool) $this->get('use.websocket', false);
    }

    private function resolveChannel($channelOrName)
    {
        $channel = $channelOrName;
        if ($channel instanceof ChannelInterface) {
            return $channel;
        }

        $channel = $this->getChannelByName($channelOrName);
        if ($channel instanceof ChannelInterface) {
            return $channel;
        }

        $channel = $this->getChannelById($channelOrName);
        if ($channel instanceof ChannelInterface) {
            return $channel;
        }

        return null;
    }
}