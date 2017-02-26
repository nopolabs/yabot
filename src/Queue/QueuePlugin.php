<?php

namespace Nopolabs\Yabot\Queue;

use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\PluginTrait;
use Nopolabs\Yabot\Plugins\ChannelPluginTrait;
use Nopolabs\Yabot\Plugins\PluginInterface;
use Psr\Log\LoggerInterface;

class QueuePlugin
{
    use PluginTrait;

    /** @var Queue */
    protected $queue;

    public function __construct(
        MessageDispatcher $dispatcher,
        LoggerInterface $logger,
        Queue $queue,
        array $config = [])
    {
        $this->setDispatcher($dispatcher);
        $this->setLog($logger);
        $this->queue = $queue;

        $default = [
            'channel' => 'general',
            'matchers' => [
                'push' => "/^push #?(?'item'[0-9]{4,5})\\b/",
                'next' => "/^next\\s*$/",
                'remove' => "/^rm #?(?'item'[0-9]{4,5})\\b/",
                'clear' => "/^clear\\s*$/",
                'list' => "/^list$/",
            ],
        ];

        $config = array_merge($default, $config);

        $channel = $config['channel'];
        $matchers = $config['matchers'];

        $matchers = $this->addToMatchers('channel', $channel, $matchers);
        if (isset($config['commandPrefix'])) {
            $prefix = $config['commandPrefix'];
            $matchers = $this->replaceInPatterns('/^', "/^$prefix ", $matchers);
        }
        $matchers = $this->replaceInPatterns(' ', "\\s+", $matchers);

        $this->setMatchers($matchers);
    }

    public function push(Message $msg, array $matches)
    {
        $element = $this->queue->buildElement($msg, $matches);

        $this->queue->push($element);

        $this->list($msg);
    }

    public function next(Message $msg, array $matches)
    {
        $this->queue->next();

        $this->list($msg);
    }

    public function remove(Message $msg, array $matches)
    {
        $element = $this->queue->buildElement($msg, $matches);

        $this->queue->remove($element);

        $this->list($msg);
    }

    public function clear(Message $msg, array $matches)
    {
        $this->queue->clear();

        $this->list($msg);
    }

    public function list(Message $msg, array $matches = [])
    {
        $results = [];

        $details = $this->queue->getDetails();

        if (empty($details)) {
            $results[] = 'The queue is empty.';
        } else {
            foreach ($this->queue->getDetails() as $detail) {
                $results[] = $detail;
            }
        }

        $msg->reply(join("\n", $results));
        $msg->setHandled(true);
    }
}