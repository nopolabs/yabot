<?php

namespace Nopolabs\Yabot\Bot;


trait PluginTrait
{
    /** @var array */
    private $matchers;

    /** @var MessageDispatcher */
    private $dispatcher;

    public function getMatchers() : array
    {
        return $this->matchers;
    }

    public function setMatchers(array $matchers)
    {
        $this->matchers = $matchers;
    }

    public function getDispatcher() : MessageDispatcher
    {
        return $this->dispatcher;
    }

    public function setDispatcher(MessageDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function onMessage(Message $message)
    {
        $this->dispatcher->dispatch($this, $message, $this->matchers);
    }

    public function expandMatchers(array $matchers) : array
    {
        $expanded = [];

        foreach ($matchers as $method => $matcher) {
            $matcher = is_array($matcher) ? $matcher : ['pattern' => $matcher];
            $expanded[$method] = $matcher;
        }

        return $expanded;
    }

    public function addToMatchers($key, $value, array $matchers) : array
    {
        $updated = [];

        foreach ($this->expandMatchers($matchers) as $method => $matcher) {
            $matcher[$key] = $value;
            $updated[$method] = $matcher;
        }

        return $updated;
    }

    public function replaceInPatterns($search, $replace, array $matchers)
    {
        $replaced = [];

        foreach ($this->expandMatchers($matchers) as $method => $matcher) {
            $pattern = $matcher['pattern'];
            $pattern = str_replace($search, $replace, $pattern);
            $matcher['pattern'] = $pattern;
            $replaced[$method] = $matcher;
        }

        return $replaced;
    }
}