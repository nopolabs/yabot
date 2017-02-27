<?php

namespace Nopolabs\Yabot\Bot;


use Exception;
use Psr\Log\LoggerInterface;

class MessageDispatcher implements MessageDispatcherInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function dispatch($plugin, MessageInterface $message, array $matchers)
    {
        foreach ($matchers as $name => $params) {
            if ($message->isHandled()) {
                return;
            }

            $matched = $this->matchMessage($message, $name, $params);

            if ($matched === false) {
                continue;
            }

            $this->dispatchMessage($plugin, $message, $matched);
        }
    }

    public function matchMessage(MessageInterface $message, $name, array $params)
    {
        $params = is_array($params) ? $params : ['pattern' => $params];

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

        $method = isset($params['method']) ? $params['method'] : $name;

        return [$method, $matches];
    }

    public function matchesChannel(array $params, MessageInterface $message)
    {
        if (isset($params['channel']) && !$message->matchesChannel($params['channel'])) {
            return false;
        } else {
            return true;
        }
    }

    public function matchesUser(array $params, MessageInterface $message)
    {
        if (isset($params['user']) && !$message->matchesUser($params['user'])) {
            return false;
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

    protected function dispatchMessage($plugin, MessageInterface $message, array $matched)
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