<?php

namespace Nopolabs\Yabot\Plugins;


use InvalidArgumentException;
use Nopolabs\Yabot\Message;
use Nopolabs\Yabot\Yabot;
use Slackyboy\Bot;
use Slackyboy\Plugins\PluginInterface;
use Slackyboy\Plugins\PluginManager;

abstract class BasePlugin implements PluginInterface
{
    protected $bot;
    protected $plugins;
    protected $matchers;

    public function __construct(Bot $bot, PluginManager $plugins)
    {
        if ($bot instanceof Yabot) {
            $this->bot = $bot;
            $this->plugins = $plugins;
            $this->matchers = [];
        } else {
            throw new InvalidArgumentException('instanceof '.static::class.' required.');
        }
    }

    public function enable()
    {
        $this->matchers = $this->prepareMatchers($this->getPluginOptions());

        $this->bot->on('message', function (Message $message) {
            $this->onMessage($message);
        });
    }

    public function getBot() : Yabot
    {
        return $this->bot;
    }

    public function verifyMethods()
    {
        foreach ($this->matchers as $name => $params) {
            $method = isset($params['method']) ? $params['method'] : $name;
            $this->verifyMethod($method);
        }
    }

    protected function getPluginOptions(array $default = []) : array
    {
        $className = static::class;
        $options = $this->bot->getConfig()->get("plugins.$className", []);

        return array_merge($default, $options);
    }

    protected function prepareMatchers(array $options) : array
    {
        return $options;
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
        if ($message->isHandled()) {
            return;
        }
        
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

    protected function load($key)
    {
        return $this->getBot()->getStorage()->get($this->storageKey($key));
    }

    protected function save($key, $data)
    {
        $this->getBot()->getStorage()->save($this->storageKey($key), $data);
    }

    protected function storageKey($key)
    {
        return str_replace('\\', '_', static::class).'.'.$key;
    }
}