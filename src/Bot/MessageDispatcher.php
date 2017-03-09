<?php

namespace Nopolabs\Yabot\Bot;


use Exception;
use Psr\Log\LoggerInterface;

class MessageDispatcher implements MessageDispatcherInterface
{
    private $logger;
    private $prefix;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->prefix = '';
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function dispatch($plugin, MessageInterface $message, array $matchers)
    {
        if ($message->isHandled()) {
            return;
        }

        if (!$message->matchesPrefix($this->prefix)) {
            return;
        }

        foreach ($matchers as $name => $params) {
            $matched = $this->matchMessage($message, $name, $params);

            if ($matched === false) {
                continue;
            }

            $this->dispatchMessage($plugin, $message, $matched);

            if ($message->isHandled()) {
                return;
            }
        }
    }

    public function matchMessage(MessageInterface $message, $name, array $params)
    {
        if (!$this->matchesIsBot($params, $message)) {
            return false;
        }

        if (!$this->matchesChannel($params, $message)) {
            return false;
        }

        if (!$this->matchesUser($params, $message)) {
            return false;
        }

        $matches = $this->matchPattern($params, $message);
        if ($matches === false) {
            return false;
        }

        $this->logger->info("matched: $name => ".json_encode($params));

        $method = $params['method'];

        return [$method, $matches];
    }

    public function matchesIsBot(array $params, MessageInterface $message)
    {
        if (isset($params['isBot'])) {
            return $message->matchesIsBot($params['isBot']);
        } else {
            return true;
        }
    }

    public function matchesChannel(array $params, MessageInterface $message)
    {
        if (isset($params['channel'])) {
            return $message->matchesChannel($params['channel']);
        } else {
            return true;
        }
    }

    public function matchesUser(array $params, MessageInterface $message)
    {
        if (isset($params['user'])) {
            return $message->matchesUser($params['user']);
        } else {
            return true;
        }
    }

    public function matchPattern(array $params, MessageInterface $message)
    {
        if (isset($params['pattern'])) {
            return $message->matchPattern($params['pattern']);
        } else {
            return [];
        }
    }

    public function dispatchMessage($plugin, MessageInterface $message, array $matched)
    {
        list($method, $matches) = $matched;

        try {
            call_user_func([$plugin, $method], $message, $matches);
        } catch (Exception $e) {
            $this->logger->warning('Exception in '.static::class.'::'.$method);
            $this->logger->warning($e->getMessage());
            $this->logger->warning($e->getTraceAsString());
        }
    }
}
