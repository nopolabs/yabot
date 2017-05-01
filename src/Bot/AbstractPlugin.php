<?php

namespace Nopolabs\Yabot\Bot;

use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;

class AbstractPlugin implements PluginInterface
{
    use PluginTrait;

    private $yabot;

    public function __construct(
        MessageDispatcher $dispatcher,
        LoggerInterface $logger,
        Yabot $yabot,
        array $config = [])
    {
        $this->setDispatcher($dispatcher);
        $this->setLog($logger);
        $this->yabot = $yabot;
    }

    protected function getYabot()
    {
        return $this->yabot;
    }
}