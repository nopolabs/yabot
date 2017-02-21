<?php

namespace Nopolabs\Yabot\Plugins;


use Nopolabs\Yabot\Message;
use Slackyboy\Bot;
use Slackyboy\Plugins\PluginInterface;
use Slackyboy\Plugins\PluginManager;

abstract class BasePlugin implements PluginInterface
{
    protected $bot;
    protected $plugins;
    protected $matchers;

    /**
     * @param Bot $bot
     * @param PluginManager $plugins
     */
    public function __construct(Bot $bot, PluginManager $plugins)
    {
        $this->bot = $bot;
        $this->plugins = $plugins;
        $this->matchers = [];
    }

    public function enable()
    {
        $this->matchers = $this->prepareMatchers($this->getConfig());

        $this->bot->on('message', function (Message $message) {
            $this->onMessage($message);
        });
    }

    public function verifyMethods()
    {
        foreach ($this->matchers as $name => $params) {
            $method = isset($params['method']) ? $params['method'] : $name;
            $this->verifyMethod($method);
        }
    }

    protected function getConfig(array $default = null) : array
    {
        return $this->bot->getConfig()->get(static::class, $default);
    }

    protected function prepareMatchers(array $config) : array
    {
        return $config;
    }

    protected function onMessage(Message $message)
    {
        foreach ($this->matchers as $name => $params) {

            $matched = $this->matchMessage($message, $name, $params);

            if ($matched === false) {
                continue;
            }

            $this->dispatchMessage($message, $matched);
        }
    }

    protected function dispatchMessage(Message $message, array $matched)
    {
        list($method, $matches) = $matched;

        $plugin = $this->getPlugin();

        call_user_func([$plugin, $method], $this->bot, $message, $matches);
    }

    protected function matchMessage(Message $message, $name, array $params)
    {
        $params = is_array($params) ? $params : ['pattern' => $params];

        if (isset($params['enabled']) && !$params['enabled']) {
            return false;
        }
        if (isset($params['disabled']) && $params['disabled']) {
            return false;
        }
        if (isset($params['channel']) && !$message->matchesChannel($params['channel'])) {
            return false;
        }
        if (isset($params['user']) && !$message->matchesUser($params['user'])) {
            return false;
        }
        if (isset($params['pattern'])) {
            $matches = $message->matchPattern($params['pattern']);
            if ($matches === false) {
                return false;
            }
        } else {
            $matches = [];
        }

        $this->bot->getLog()->info("matched: $name");

        $method = isset($params['method']) ? $params['method'] : $name;

        return [$method, $matches];
    }

    protected function verifyMethod($method)
    {
        try {
            $reflect = new \ReflectionClass($this->getPlugin());
            if (!$reflect->getMethod($method)->isPublic()) {
                throw new \Exception('The method "'.static::class.'::'.$method.'" is not public.');
            }
        } catch (\Exception $e) {
            throw new \Exception('Error inspecting "'.static::class.'": '.$e->getMessage());
        }
    }

    protected function getPlugin()
    {
        return $this->plugins->getPlugins()[static::class];
    }
}