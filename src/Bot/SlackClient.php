<?php

namespace Nopolabs\Yabot\Bot;


use Nopolabs\Yabot\Helpers\ConfigTrait;
use React\Promise\PromiseInterface;
use Slack\Channel;
use Slack\ChannelInterface;
use Slack\Payload;
use Slack\RealTimeClient;
use Slack\User;

class SlackClient
{
    use ConfigTrait;

    /** @var RealTimeClient */
    private $slack;

    /** @var Users */
    private $users;

    /** @var Channels */
    private $channels;

    /** @var User */
    protected $authedUser;

    public function __construct(RealTimeClient $slack, Users $users, Channels $channels, array $config = [])
    {
        $this->slack = $slack;
        $this->users = $users;
        $this->channels = $channels;
        $this->setConfig($config);
    }

    public function getRealTimeClient()
    {
        return $this->slack;
    }

    public function init()
    {
        $this->initChannelUpdateHandlers();
        $this->initUserUpdateHandlers();
    }

    public function update()
    {
        $this->updateAuthedUser();
        $this->updateUsers();
        $this->updateChannels();
    }

    public function getAuthedUser()
    {
        return $this->authedUser;
    }

    public function connect() : PromiseInterface
    {
        return $this->slack->connect();
    }

    public function disconnect()
    {
        return $this->slack->disconnect();
    }

    public function useWebSocket() : bool
    {
        return (bool) $this->get('use.websocket', false);
    }

    public function say($text, $channel, array $additionalParameters = [])
    {
        if (!($channel instanceof ChannelInterface)) {
            $channel = $this->channelByName($channel);
            if (!$channel) {
                $channel = $this->channelById($channel);
            }
        }

        if (empty($additionalParameters) && $this->useWebSocket()) {
            // WebSocket send does not support message formatting.
            $this->send($text, $channel);
        } else {
            // Http post send supports message formatting.
            $this->post($text, $channel, $additionalParameters);
        }
    }

    public function send($text, ChannelInterface $channel)
    {
        $this->slack->send($text, $channel);
    }

    public function post($text, ChannelInterface $channel, array $additionalParameters = [])
    {
        $parameters = array_merge([
            'text' => $text,
            'channel' => $channel->getId(),
            'as_user' => true,
        ], $additionalParameters);

        $this->slack->apiCall('chat.postMessage', $parameters);
    }

    public function on($event, array $onMessage)
    {
        $this->slack->on($event, function (Payload $payload) use ($onMessage) {
            call_user_func($onMessage, $payload);
        });
    }

    /**
     * @param $id
     * @return null|User
     */
    public function userById($id)
    {
        return $this->users->byId($id);
    }

    /**
     * @param $name
     * @return null|User
     */
    public function userByName($name)
    {
        return $this->users->byName($name);
    }

    /**
     * @param $id
     * @return null|Channel
     */
    public function channelById($id)
    {
        return $this->channels->byId($id);
    }

    /**
     * @param $name
     * @return null|Channel
     */
    public function channelByName($name)
    {
        return $this->channels->byName($name);
    }

    public function updateUsers()
    {
        $this->slack->getUsers()->then(function(array $users) {
            $this->users->update($users);
        });
    }

    public function updateChannels()
    {
        $this->slack->getChannels()->then(function(array $channels) {
            $this->channels->update($channels);
        });
    }

    public function updateAuthedUser()
    {
        $this->slack->getAuthedUser()->then(function (User $user) {
            $this->authedUser = $user;
        });
    }

    protected function initChannelUpdateHandlers()
    {
        $events = ['channel_created', 'channel_deleted', 'channel_rename'];
        foreach ($events as $event) {
            $this->on($event, [$this, 'updateChannels']);
        }
    }

    protected function initUserUpdateHandlers()
    {
        $events = ['user_change'];
        foreach ($events as $event) {
            $this->on($event, [$this, 'updateUsers']);
        }
    }
}