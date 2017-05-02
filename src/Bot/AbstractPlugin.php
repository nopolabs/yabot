<?php

namespace Nopolabs\Yabot\Bot;

use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;

class AbstractPlugin implements PluginInterface
{
    use PluginTrait;

    private $yabot;

    public function __construct(LoggerInterface $logger, Yabot $yabot)
    {
        $this->setLog($logger);
        $this->yabot = $yabot;
    }

    protected function getYabot()
    {
        return $this->yabot;
    }
}