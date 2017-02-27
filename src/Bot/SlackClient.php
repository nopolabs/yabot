<?php

namespace Nopolabs\Yabot\Bot;


use React\Promise\PromiseInterface;
use Slack\ChannelInterface;
use Slack\Payload;
use Slack\RealTimeClient;
use Slack\User;

class SlackClient
{
    /** @var RealTimeClient */
    private $slack;

    /** @var Users */
    private $users;

    /** @var Channels */
    private $channels;

    /** @var User */
    protected $authedUser;

    public function __construct(RealTimeClient $slack, Users $users, Channels $channels)
    {
        $this->slack = $slack;
        $this->users = $users;
        $this->channels = $channels;
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

    public function connect() : PromiseInterface
    {
        return $this->slack->connect();
    }

    public function disconnect()
    {
        return $this->slack->disconnect();
    }

    public function say($text, ChannelInterface $channel)
    {
        $this->slack->send($text, $channel);
    }

    public function on($event, array $onMessage)
    {
        $this->slack->on($event, function (Payload $payload) use ($onMessage) {
            call_user_func_array($onMessage, [$payload]);
        });
    }

    public function userById($id) : User
    {
        return $this->users->byId($id);
    }

    public function userByName($name) : User
    {
        return $this->users->byName($name);
    }

    public function channelById($id) : ChannelInterface
    {
        return $this->channels->byId($id);
    }

    public function channelByName($name) : ChannelInterface
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