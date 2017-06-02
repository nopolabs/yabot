<?php

namespace Nopolabs\Yabot\Plugin;


use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Helpers\MatcherTrait;
use Nopolabs\Yabot\Message\Message;
use Psr\Log\LoggerInterface;

class MethodMatcher
{
    use LogTrait;
    use MatcherTrait;

    private $method;

    public function __construct(
        string $name,
        $isBot,
        array $channels,
        array $users,
        array $patterns,
        string $method,
        LoggerInterface $logger)
    {
        $this->setName($name);
        $this->setIsBot($isBot);
        $this->setChannels($channels);
        $this->setUsers($users);
        $this->setPatterns($patterns);
        $this->setLog($logger);

        $this->method = $method;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}