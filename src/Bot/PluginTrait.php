<?php

namespace Nopolabs\Yabot\Bot;

use Nopolabs\Yabot\Helpers\LogTrait;

trait PluginTrait
{
    use LogTrait;

    /** @var array */
    private $matchers;

    /** @var MessageDispatcher */
    private $dispatcher;

    public function onMessage(MessageInterface $message)
    {
        $this->dispatcher->dispatch($this, $message, $this->matchers);
    }

    public function getDispatcher() : MessageDispatcher
    {
        return $this->dispatcher;
    }

    public function setDispatcher(MessageDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getMatchers() : array
    {
        return $this->matchers;
    }

    public function setMatchers(array $matchers)
    {
        $matchers = $this->expandMatchers($matchers);

        foreach ($matchers as $name => $params) {
            $this->getLog()->debug("$name: ".json_encode($params));
        }

        $this->matchers = $matchers;
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