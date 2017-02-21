<?php

namespace Nopolabs\Yabot;


use Exception;
use Noodlehaus\Config;
use Nopolabs\Yabot\Plugins\BasePlugin;
use Slack\Channel;
use Slack\Payload;
use Slack\User;

class Bot extends \Slackyboy\Bot
{
    protected $configPath;

    /** @var User */
    protected $botUser;

    /** @var User[] */
    protected $users;
    protected $usersById;
    protected $usersByName;

    /** @var Channel[] */
    protected $channels;
    protected $channelsById;
    protected $channelsByName;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->users = [];
        $this->usersById = [];
        $this->usersByName = [];
        $this->channels = [];
        $this->channelsById = [];
        $this->channelsByName = [];

        parent::__construct();

        foreach ($this->plugins->getPlugins() as $plugin) {
            if ($plugin instanceof BasePlugin) {
                $plugin->verifyMethods();
            }
        }
    }

    public function loadConfig()
    {
        // if no config file exists, create the default
        if (!is_file($this->getConfigPath())) {
            $this->createDefaultConfig();
        }

        // load config
        $this->config = new Config($this->getConfigPath());
    }

    public function getUserByName($name)
    {
        if (isset($this->usersByName[$name])) {
            return $this->users[$this->usersByName[$name]];
        }

        throw new Exception("The user \"$name\" does not exist.");
    }

    public function getUserById($id)
    {
        if (isset($this->usersById[$id])) {
            return $this->users[$this->usersById[$id]];
        }

        throw new Exception("The user id \"$id\" does not exist.");
    }

    public function getChannelByName($name)
    {
        if (isset($this->channelsByName[$name])) {
            return $this->channels[$this->channelsByName[$name]];
        }

        throw new Exception("The channel \"$name\" does not exist.");
    }

    public function getChannelById($id)
    {
        if (isset($this->channelsById[$id])) {
            return $this->channels[$this->channelsById[$id]];
        }

        throw new Exception("The channel id \"$id\" does not exist.");
    }

    public function reply(Message $msg, $text)
    {
        $this->say($text, $this->getChannelById($msg->getChannelId()));
    }

    public function run()
    {
        $this->client->on('message', function (Payload $data) {
            $message = new Message($this, $data->getData());

            $this->log->info('Noticed message', [
                'text' => $message->getText(),
            ]);

            $this->emit('message', [$message]);

            if ($message->matchesAny(
                '/\b'.$this->botUser->getUsername().'\b/i',
                '/\b<@'.$this->botUser->getId().'>\b/i'))
            {
                $this->log->debug('Mentioned in message', [$message]);
                $this->emit('mention', [$message]);
            }
        });

        $this->initChannelUpdateHandlers();
        $this->initUserUpdateHandlers();

        $this->client->connect()->then(function () {
            $this->getBotUser();
            $this->updateUsers();
            $this->updateChannels();
        });

        $this->loop->run();
    }

    public function getLog()
    {
        return $this->log;
    }

    public function updateUsers()
    {
        $this->client->getUsers()->then(function(array $users) {
            $this->users = $users;
            foreach ($this->users as $index => $user) {
                $this->usersById[$user->getId()] = $index;
                $this->usersByName[$user->getUsername()] = $index;
            }
        });
    }

    public function updateChannels()
    {
        $this->client->getChannels()->then(function(array $channels) {
            $this->channels = $channels;
            foreach ($this->channels as $index => $channel) {
                $this->channelsById[$channel->getId()] = $index;
                $this->channelsByName[$channel->getName()] = $index;
            }
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

    protected function getBotUser()
    {
        $this->client->getAuthedUser()->then(function (User $user) {
            $this->botUser = $user;
            $this->log->info('Bot user name is configured as '.$user->getUsername());
        });
    }

    protected function getConfigPath()
    {
        return $this->configPath;
    }

    protected function createDefaultConfig()
    {
        if (file_exists($this->getConfigPath())) {
            $this->log->info("{$this->getConfigPath()} already exists");
            return;
        }

        // copy packaged default into config location
        copy(dirname(__DIR__).'/config.php.example', $this->getConfigPath());

        $this->log->info("created {$this->getConfigPath()}");
    }
}
