<?php

namespace Nopolabs\Yabot\Helpers;


use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait LogTrait
{
    private $log;

    protected function setLog(LoggerInterface $log = null)
    {
        $this->log = $log ?? new NullLogger();
    }

    /**
     * @return LoggerInterface|null
     */
    protected function getLog()
    {
        return $this->log;
    }

    protected function emergency($message, array $context = array())
    {
        $this->getLog()->emergency($message, $context);
    }

    protected function alert($message, array $context = array())
    {
        $this->getLog()->alert($message, $context);
    }

    protected function critical($message, array $context = array())
    {
        $this->getLog()->critical($message, $context);
    }

    protected function error($message, array $context = array())
    {
        $this->getLog()->error($message, $context);
    }

    protected function warning($message, array $context = array())
    {
        $this->getLog()->warning($message, $context);
    }

    protected function notice($message, array $context = array())
    {
        $this->getLog()->notice($message, $context);
    }

    protected function info($message, array $context = array())
    {
        $this->getLog()->info($message, $context);
    }

    protected function debug($message, array $context = array())
    {
        $this->getLog()->debug($message, $context);
    }

    protected function log($level, $message, array $context = array())
    {
        $this->getLog()->log($level, $message, $context);
    }
}