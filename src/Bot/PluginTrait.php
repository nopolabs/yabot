<?php

namespace Nopolabs\Yabot\Bot;

use Exception;
use Nopolabs\Yabot\Helpers\LogTrait;

trait PluginTrait
{
    use LogTrait;

    private $pluginId;

    private $config = [];

    public function help() : string
    {
        return 'no help available';
    }

    public function status() : string
    {
        return 'running';
    }

    public function init(string $pluginId, array $params)
    {
        $this->pluginId = $pluginId;
        $this->config = $this->canonicalConfig(array_merge($this->config, $params));

        $this->getLog()->info("$pluginId config:", $this->config);
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function getConfig() : array
    {
        return $this->config;
    }

    public function getPrefix() : string
    {
        return $this->config['prefix'];
    }

    public function getUser() : string
    {
        return $this->config['user'];
    }

    public function getChannel() : string
    {
        return $this->config['channel'];
    }

    /**
     * @return bool|null
     */
    public function getIsBot()
    {
        return $this->config['isBot'];
    }

    public function getMatchers() : array
    {
        return $this->config['matchers'];
    }

    public function dispatch(MessageInterface $message, string $text)
    {
        if ($message->isHandled()) {
            return;
        }

        if (!$message->matchesIsBot($this->getIsBot())) {
            $this->getLog()->debug('plugin isBot');
            return;
        }

        if (!$message->matchesChannel($this->getChannel())) {
            $this->getLog()->debug('plugin channel');
            return;
        }

        if (!$message->matchesUser($this->getUser())) {
            $this->getLog()->debug('plugin user');
            return;
        }

        foreach ($this->getMatchers() as $name => $params) {
            if (!$message->matchesIsBot($params['isBot'])) {
                $this->getLog()->debug('matcher isBot');
                return;
            }

            if (!$message->matchesChannel($params['channel'])) {
                $this->getLog()->debug('matcher channel');
                return;
            }

            if (!$message->matchesUser($params['user'])) {
                $this->getLog()->debug('matcher user');
                return;
            }

            if (!($matches = $message->matchPatterns($params['patterns'], $text))) {
                return;
            }

            $this->getLog()->info("matched: $name", $params);

            $this->dispatchMessage($message, [$params['method'], $matches]);

            if ($message->isHandled()) {
                return;
            }
        }
    }

    protected function dispatchMessage(MessageInterface $message, array $matched)
    {
        list($method, $matches) = $matched;

        try {
            if (method_exists($this, $method)) {
                $this->$method($message, $matches);
            } else {
                $this->getLog()->warning("{$this->pluginId} no method named: $method");
            }
        } catch (Exception $e) {
            $this->getLog()->warning('Exception in '.static::class.'::'.$method);
            $this->getLog()->warning($e->getMessage());
            $this->getLog()->warning($e->getTraceAsString());
        }
    }

    protected function canonicalConfig(array $config) : array
    {
        $config['prefix'] = $config['prefix'] ?? '';
        $config['isBot'] = $config['isBot'] ?? null;
        $config['channel'] = $config['channel'] ?? '';
        $config['user'] = $config['user'] ?? '';
        $config['matchers'] = $this->canonicalMatchers($config['matchers'] ?? []);

        return $config;
    }

    protected function canonicalMatchers(array $matchers) : array
    {
        $expanded = [];

        foreach ($matchers as $name => $params) {
            $params = is_array($params) ? $params : ['patterns' => [$params]];
            $params['isBot'] = $params['isBot'] ?? null;
            $params['channel'] = $params['channel'] ?? '';
            $params['user'] = $params['user'] ?? '';
            $params['method'] = $params['method'] ?? $name;

            if (!method_exists($this, $params['method'])) {
                $this->getLog()->warning("{$this->pluginId} no method named: {$params['method']}");
            }

            $expanded[$name] = $params;
        }

        if (!$expanded) {
            $this->getLog()->warning("{$this->pluginId} has no matchers");
        }

        return $expanded;
    }
}