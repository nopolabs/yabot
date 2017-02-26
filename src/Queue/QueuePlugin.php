<?php

namespace Nopolabs\Yabot\Queue;

use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
use Psr\Log\LoggerInterface;

class QueuePlugin implements PluginInterface
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

    public function push(MessageInterface $msg, array $matches)
    {
        $element = $this->queue->buildElement($msg, $matches);

        $this->queue->push($element);

        $this->list($msg);
    }

    public function next(MessageInterface $msg, array $matches)
    {
        $this->queue->next();

        $this->list($msg);
    }

    public function remove(MessageInterface $msg, array $matches)
    {
        $element = $this->queue->buildElement($msg, $matches);

        $this->queue->remove($element);

        $this->list($msg);
    }

    public function clear(MessageInterface $msg, array $matches)
    {
        $this->queue->clear();

        $this->list($msg);
    }

    public function list(MessageInterface $msg, array $matches = [])
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