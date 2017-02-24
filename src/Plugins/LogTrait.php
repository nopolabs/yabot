<?php

namespace Nopolabs\Yabot\Plugins;


use Psr\Log\LoggerInterface;

trait LogTrait
{
    private $log;

    protected function setLog(LoggerInterface $log)
    {
        $this->log = $log;
    }

    protected function getLog() : LoggerInterface
    {
        return $this->log;
    }
}