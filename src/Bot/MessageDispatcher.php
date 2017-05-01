<?php

namespace Nopolabs\Yabot\Bot;


use Exception;
use Psr\Log\LoggerInterface;

class MessageDispatcher implements MessageDispatcherInterface
{
    private $logger;
    private $prefix;
    private $matchers;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->prefix = '';
        $this->matchers = [];
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setMatchers(array $matchers)
    {
        $this->matchers = $matchers;
    }

    public function getMatchers()
    {
        return $this->matchers;
    }

    public function dispatch($plugin, MessageInterface $message)
    {
        if ($message->isSelf()) {
            return;
        }

        if ($message->isHandled()) {
            return;
        }

        if (!($matches = $message->matchesPrefix($this->getPrefix()))) {
            return;
        }

        $text = ltrim($matches[1]);

        foreach ($this->getMatchers() as $name => $params) {
            if (!($matched = $this->matchMessage($message, $name, $params, $text))) {
                continue;
            }

            $this->dispatchMessage($plugin, $message, $matched);

            if ($message->isHandled()) {
                return;
            }
        }
    }

    protected function matchMessage(MessageInterface $message, $name, array $params, string $text)
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

        ;
        if (!($matches = $this->matchPattern($params, $message, $text))) {
            return false;
        }

        $this->logger->info("matched: $name => ".json_encode($params));

        $method = $params['method'];

        return [$method, $matches];
    }

    protected function matchesIsBot(array $params, MessageInterface $message)
    {
        if (isset($params['isBot'])) {
            return $message->matchesIsBot($params['isBot']);
        } else {
            return true;
        }
    }

    protected function matchesChannel(array $params, MessageInterface $message)
    {
        if (isset($params['channel'])) {
            return $message->matchesChannel($params['channel']);
        } else {
            return true;
        }
    }

    protected function matchesUser(array $params, MessageInterface $message)
    {
        if (isset($params['user'])) {
            return $message->matchesUser($params['user']);
        } else {
            return true;
        }
    }

    protected function matchPattern(array $params, MessageInterface $message, string $text)
    {
        if (isset($params['pattern'])) {
            return $message->matchPattern($params['pattern'], $text);
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
