<?php

namespace Nopolabs\Yabot\Tests\Plugin;

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
}
