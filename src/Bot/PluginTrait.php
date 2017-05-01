<?php

namespace Nopolabs\Yabot\Bot;

use Nopolabs\Yabot\Helpers\LogTrait;

trait PluginTrait
{
    use LogTrait;

    /** @var MessageDispatcher */
    private $dispatcher;

    /** @var array */
    private $config;

    public function help() : string
    {
        return 'no help available';
    }

    public function status() : string
    {
        return 'running';
    }

    public function onMessage(MessageInterface $message)
    {
        $this->dispatcher->dispatch($this, $message);
    }

    public function init(array $config)
    {
        $this->config = $config;
    }

    public function getConfig() : array
    {
        return $this->config;
    }

    public function getDispatcher() : MessageDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function setDispatcher(MessageDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function setPrefix(string $prefix)
    {
        $this->getDispatcher()->setPrefix($prefix);
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