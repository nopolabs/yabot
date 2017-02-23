<?php

namespace Nopolabs\Yabot\Plugins;


use Exception;
use Nopolabs\Yabot\Message;
use Nopolabs\Yabot\Storage\StorageInterface;
use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;

trait PluginTrait
{
    private $bot;
    private $config;
    private $matchers;

    public function onMessage(Message $message)
    {
        foreach ($this->getMatchers() as $name => $params) {

            $matched = $this->matchMessage($message, $name, $params);

            if ($matched === false) {
                continue;
            }

            $this->dispatchMessage($message, $matched);
        }
    }

    public function setBot(Yabot $bot)
    {
        $this->bot = $bot;
    }

    public function setConfig(array $config)
    {
        $default = $this->getDefaultConfig();
        $this->config = array_merge($default, $config);
    }

    public function prepare()
    {
        $this->matchers = $this->config;
    }

    public function getDefaultConfig() : array
    {
        return [];
    }

    protected function getBot() : Yabot
    {
        return $this->bot;
    }

    protected function getMatchers() : array
    {
        return $this->matchers;
    }

    protected function getLog() : LoggerInterface
    {
        return $this->getBot()->getLog();
    }

    protected function getStorage() : StorageInterface
    {
        return $this->getBot()->getStorage();
    }

    protected function dispatchMessage(Message $message, array $matched)
    {
        if ($message->isHandled()) {
            return;
        }

        list($method, $matches) = $matched;

        try {
            call_user_func([static::class, $method], $message, $matches);
        } catch (Exception $e) {
            $this->getLog()->warning('Exception in '.static::class.'::'.$method);
            $this->getLog()->warning($e->getMessage());
            $this->getLog()->warning($e->getTraceAsString());
        }
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

        $this->getLog()->info("matched: $name");

        $method = isset($params['method']) ? $params['method'] : $name;

        return [$method, $matches];
    }
}