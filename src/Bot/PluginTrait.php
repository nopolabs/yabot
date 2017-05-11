<?php

namespace Nopolabs\Yabot\Bot;

use Exception;
use Nopolabs\Yabot\Helpers\ConfigTrait;
use Nopolabs\Yabot\Helpers\LogTrait;
use Throwable;

trait PluginTrait
{
    use LogTrait;
    use ConfigTrait;

    private $pluginId;

    public function help(): string
    {
        return $this->get('help', 'no help available');
    }

    public function status(): string
    {
        return 'running';
    }

    public function init(string $pluginId, array $params)
    {
        $this->setPluginId($pluginId);
        $this->overrideConfig($params);
        $this->checkMatchers($this->get('matchers'));

        $this->getLog()->info("inited $pluginId config:", $this->getConfig());
    }

    public function getPrefix(): string
    {
        return $this->get('prefix');
    }

    public function dispatch(MessageInterface $message, string $text)
    {
        if ($message->isHandled()) {
            return;
        }

        if (!$message->matchesIsBot($this->getIsBot())) {
            $data = [
                'message' => $message->isBot(),
                'plugin' => $this->getIsBot(),
            ];
            $this->getLog()->debug('isBot match failed: '.json_encode($data));
            return;
        }

        if (!$message->matchesChannel($this->getChannel())) {
            $data = [
                'message' => $message->getChannel(),
                'plugin' => $this->getChannel(),
            ];
            $this->getLog()->debug('channel match failed: '.json_encode($data));
            return;
        }

        if (!$message->matchesUser($this->getUser())) {
            $data = [
                'message' => $message->getUser(),
                'plugin' => $this->getUser(),
            ];
            $this->getLog()->debug('user match failed: '.json_encode($data));
            return;
        }

        foreach ($this->getMatchers() as $name => $params) {
            if (!$message->matchesIsBot($params['isBot'])) {
                $this->getLog()->debug('!matchesIsBot');
                continue;
            }

            if (!$message->matchesChannel($params['channel'])) {
                $this->getLog()->debug('!matchesChannel');
                continue;
            }

            if (!$message->matchesUser($params['user'])) {
                $this->getLog()->debug('!matchesUser');
                continue;
            }

            if (!($matches = $message->matchPatterns($params['patterns'], $text))) {
                continue;
            }

            if (!$this->validMatch($message, $params, $matches)) {
                continue;
            }

            $this->getLog()->info("matched: $name", ['params' => $params, 'matches' =>$matches]);

            $this->dispatchMessage($message, [$params['method'], $matches]);

            if ($message->isHandled()) {
                return;
            }
        }
    }

    public function replaceInPatterns($search, $replace, array $matchers): array
    {
        $replaced = [];
        foreach ($matchers as $name => $params) {
            $params['patterns'] = array_map(function ($pattern) use ($search, $replace) {
                return str_replace($search, $replace, $pattern);
            }, $params['patterns']);
            $replaced[$name] = $params;
        }
        return $replaced;
    }

    protected function validMatch(MessageInterface $message, array $params, array $matches) : bool
    {
        // plugins may override this method to have final say on dispatching
        return true;
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
            $this->getLog()->warning('Exception in ' . static::class . '::' . $method);
            $this->getLog()->warning($e->getMessage());
            $this->getLog()->warning($e->getTraceAsString());
        }
    }

    protected function overrideConfig(array $params)
    {
        $config = $this->canonicalConfig(array_merge($this->getConfig(), $params));

        $this->setConfig($config);
    }

    protected function setPluginId($pluginId)
    {
        $this->pluginId = $pluginId;
    }

    protected function getPluginId()
    {
        return $this->pluginId;
    }

    protected function getUser()
    {
        return $this->get('user');
    }

    protected function getChannel()
    {
        return $this->get('channel');
    }

    /**
     * @return bool|null
     */
    protected function getIsBot()
    {
        return $this->get('isBot');
    }

    protected function getMatchers(): array
    {
        return $this->get('matchers');
    }

    protected function canonicalConfig(array $config): array
    {
        $config['prefix'] = $config['prefix'] ?? '';
        $config['isBot'] = $config['isBot'] ?? null;
        $config['channel'] = $config['channel'] ?? '';
        $config['user'] = $config['user'] ?? '';
        $config['matchers'] = $this->canonicalMatchers($config['matchers'] ?? []);

        return $config;
    }

    protected function canonicalMatchers(array $matchers): array
    {
        $expanded = [];

        foreach ($matchers as $name => $params) {
            $params = is_array($params) ? $params : ['patterns' => [$params]];
            if (isset($params['pattern'])) {
                $params['patterns'] = [$params['pattern']];
                unset($params['pattern']);
            }
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

    protected function checkMatchers(array $matchers)
    {
        foreach ($matchers as $name => $params) {
            foreach ($params['patterns'] as $pattern) {
                try {
                    preg_match($pattern, '', $matches);
                } catch (Throwable $e) {
                    $this->getLog()->warning("$name.pattern='$pattern' " . $e->getMessage());
                }
            }
        }
    }
}