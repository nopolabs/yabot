<?php

namespace Nopolabs\Yabot\Helpers;


trait ConfigTrait
{
    /** @var array */
    private $config = [];

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    protected function getConfig() : array
    {
        return $this->config;
    }

    protected function has($key) : bool
    {
        return isset($this->config[$key]);
    }

    protected function get($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}