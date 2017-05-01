<?php

namespace Nopolabs\Yabot\Bot;

use Nopolabs\Yabot\Helpers\LogTrait;

trait PluginTrait
{
    use LogTrait;

    private $prefix;

    private $config = [];

    /** @var MessageDispatcher */
    private $dispatcher;

    public function help() : string
    {
        return 'no help available';
    }

    public function status() : string
    {
        return 'running';
    }

    public function init(array $params)
    {
        $this->config = array_merge($this->config, $params);

        $this->prefix = $this->config['prefix'] ?? '';

        $this->setMatchers($this->config['matchers']);
    }

    public function setConfig(array $params)
    {
        $this->config = $params;
    }

    public function getPrefix() : string
    {
        return $this->prefix;
    }

    public function dispatch(MessageInterface $message, string $text)
    {
        $this->dispatcher->dispatch($this, $message, $text);
    }

    public function getDispatcher() : MessageDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function setDispatcher(MessageDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function setMatchers(array $matchers)
    {
        $matchers = $this->expandMatchers($matchers);

        foreach ($matchers as $name => $params) {
            $this->getLog()->debug("$name: ".json_encode($params));
        }

        $this->getDispatcher()->setMatchers($matchers);
    }

    public function expandMatchers(array $matchers) : array
    {
        $expanded = [];

        foreach ($matchers as $name => $params) {
            $params = is_array($params) ? $params : ['pattern' => $params];
            if (!isset($params['method'])) {
                $params['method'] = $name;
            }
            $expanded[$name] = $params;
        }

        return $expanded;
    }

    public function addToMatchers($key, $value, array $matchers) : array
    {
        $updated = [];

        foreach ($this->expandMatchers($matchers) as $name => $params) {
            $params[$key] = $value;
            $updated[$name] = $params;
        }

        return $updated;
    }

    public function replaceInPatterns($search, $replace, array $matchers)
    {
        $replaced = [];

        foreach ($this->expandMatchers($matchers) as $name => $params) {
            $pattern = $params['pattern'];
            $pattern = str_replace($search, $replace, $pattern);
            $params['pattern'] = $pattern;
            $replaced[$name] = $params;
        }

        return $replaced;
    }
}