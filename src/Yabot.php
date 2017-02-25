<?php

namespace Nopolabs\Yabot;

use Evenement\EventEmitterTrait;
use Nopolabs\Yabot\Bot\MessageFactory;
use Nopolabs\Yabot\Bot\SlackClient;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Slack\Payload;

class Yabot
{
    use EventEmitterTrait;

    /** @var LoggerInterface */
    protected $logger;

    /** @var LoopInterface */
    protected $eventLoop;

    /** @var SlackClient */
    protected $slackClient;

    /** @var MessageFactory */
    protected $messageFactory;

    public function __construct(
        LoggerInterface $logger,
        LoopInterface $eventLoop,
        SlackClient $slackClient,
        MessageFactory $messageFactory
    ) {
        $this->logger = $logger;
        $this->eventLoop = $eventLoop;
        $this->slackClient = $slackClient;
        $this->messageFactory = $messageFactory;
    }

    public function run()
    {
        $this->slackClient->on('message', function (Payload $data) {
            $message = $this->messageFactory->create($data);

            $this->logger->info('Noticed message', [
                'text' => $message->getText(),
            ]);

            $this->emit('message', [$message]);
        });

        $this->slackClient->init();

        $this->slackClient->connect()->then(function () {
            $this->slackClient->update();
        });

        $this->eventLoop->run();
    }

    public function quit()
    {
        $this->logger->info('Quitting');
        $this->slackClient->disconnect();
    }

    public function addPlugin($plugin)
    {
        $this->on('message', [$plugin, 'onMessage']);
    }

    public function removePlugin($plugin)
    {
        $this->removeListener('message', [$plugin, 'onMessage']);
    }
}
