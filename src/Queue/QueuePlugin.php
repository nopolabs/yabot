<?php

namespace Nopolabs\Yabot\Queue;

use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Plugins\ChannelPluginTrait;
use Nopolabs\Yabot\Plugins\PluginInterface;

class QueuePlugin implements PluginInterface
{
    use ChannelPluginTrait;

    /** @var Queue */
    protected $queue;

    public function getDefaultConfig() : array
    {
        return [
            'queueClass' => 'Nopolabs\\Yabot\\Queue\\Queue',
            'storageName' => 'queue',
            'channel' => 'general',
            'matchers' => [
                'push' => "/^push #?(?'element'[0-9]{4,5})\\b/",
                'next' => "/^next\\b/",
                'remove' => "/^rm #?(?'element'[0-9]{4,5})\\b/",
                'clear' => "/^clear\\b/",
                'list' => "/^list\\b/",
            ],
        ];
    }

    public function prepare()
    {
        $queueClass = $this->config['queueClass'];
        $this->queue = new $queueClass($this->getBot(), $this->config);

        $channel = $this->config['channel'];
        $matchers = $this->config['matchers'];
        $matchers = $this->addChannelToMatchers($channel, $matchers);
        $matchers = $this->replaceInPatterns(' ', "\\s+", $matchers);
        $this->matchers = $matchers;
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
        $details = $this->queue->getDetails();

        if (empty($details)) {
            $msg->reply('The queue is empty.');
        } else {
            $list = [];
            foreach ($this->queue->getDetails() as $detail) {
                $list[] = $detail;
            }
            $msg->reply(join("\n", $list));
        }

        $msg->setHandled(true);
    }
}