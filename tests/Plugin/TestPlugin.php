<?php

namespace Nopolabs\Yabot\Tests\Plugin;

use Nopolabs\Yabot\Plugin\PluginMatcher;
use Nopolabs\Yabot\Plugin\PluginTrait;
use Psr\Log\LoggerInterface;

class TestPlugin
{
    use PluginTrait;

    public function __construct(LoggerInterface $logger)
    {
        $this->setLog($logger);
    }

    public function getConfigTest() : array
    {
        return $this->getConfig();
    }

    public function setPluginMatcher(PluginMatcher $pluginMatcher)
    {
        $this->pluginMatcher = $pluginMatcher;
    }

    public function setMethodMatchers(array $methodMatchers)
    {
        $this->methodMatchers = $methodMatchers;
    }
}
