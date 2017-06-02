<?php

namespace Nopolabs\Yabot\Plugin;


use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Helpers\MatcherTrait;
use Nopolabs\Yabot\Message\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class PluginMatcher
{
    use LogTrait;
    use MatcherTrait;

    public function __construct(
        string $pluginId,
        bool $isBot = null,
        array $channels,
        array $users,
        LoggerInterface $logger = null)
    {
        $this->setName($pluginId);
        $this->setIsBot($isBot);
        $this->setChannels($channels);
        $this->setUsers($users);
        $this->setLog($logger);
    }
}
