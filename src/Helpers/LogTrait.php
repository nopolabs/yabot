<?php

namespace Nopolabs\Yabot\Helpers;


use Psr\Log\LoggerInterface;

trait LogTrait
{
    private $log;

    protected function setLog(LoggerInterface $log = null)
    {
        $this->log = $log;
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
        if ($log = $this->getLog()) {
            $log->emergency($message, $context);
        }
    }

    protected function alert($message, array $context = array())
    {
        if ($log = $this->getLog()) {
            $log->alert($message, $context);
        }
    }

    protected function critical($message, array $context = array())
    {
        if ($log = $this->getLog()) {
            $log->critical($message, $context);
        }
    }

    protected function error($message, array $context = array())
    {
        if ($log = $this->getLog()) {
            $log->error($message, $context);
        }
    }

    protected function warning($message, array $context = array())
    {
        if ($log = $this->getLog()) {
            $log->warning($message, $context);
        }
    }

    protected function notice($message, array $context = array())
    {
        if ($log = $this->getLog()) {
            $log->notice($message, $context);
        }
    }

    protected function info($message, array $context = array())
    {
        if ($log = $this->getLog()) {
            $log->info($message, $context);
        }
    }

    protected function debug($message, array $context = array())
    {
        if ($log = $this->getLog()) {
            $log->debug($message, $context);
        }
    }

    protected function log($level, $message, array $context = array())
    {
        if ($log = $this->getLog()) {
            $log->log($level, $message, $context);
        }
    }
}