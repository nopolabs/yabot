<?php

namespace Nopolabs\Yabot\Message;

use Exception;
use Nopolabs\Yabot\Helpers\SlackTrait;
use Nopolabs\Yabot\Slack\Client;
use Slack\Channel;

class MessageFactory
{
    use SlackTrait;

    public function __construct(Client $slackClient)
    {
        $this->setSlack($slackClient);
    }

    public function create(array $data) : Message
    {
        $formattedText = $this->assembleFormattedText($data);
        $user = $this->getUser($data);
        $channel = $this->getChannel($data);

        return new Message($this->getSlack(), $data, $formattedText, $user, $channel);
    }

    public function getUser(array $data)
    {
        if ($this->isMessageChanged($data) && isset($data['message']['user'])) {
            return $this->getUserById($data['message']['user'] ?? null);
        }

        return $this->getUserById($data['user'] ?? null);
    }

    public function getChannel(array $data) : Channel
    {
        $channel = $this->getChannelById($data['channel']);

        if ($channel instanceof Channel) {
            return $channel;
        }

        throw new Exception('Message data does not have a channel.');
    }

    public function assembleFormattedText(array $data) : string
    {
        $formatted = [$this->formatMessageText($data)];

        $formatted = array_merge($formatted, $this->formatAttachmentsText($data));

        return trim(implode("\n", array_filter($formatted)));
    }

    public function formatMessageText(array $data) : string
    {
        if ($this->isMessageChanged($data) && isset($data['message']['text'])) {
            return $this->formatText($data['message']['text']);
        }

        if (isset($data['text'])) {
            return $this->formatText($data['text']);
        }

        return '';
    }

    public function formatAttachmentsText(array $data) : array
    {
        $formatted = [];
        if (isset($data['attachments'])) {
            foreach ($data['attachments'] as $attachment) {
                if (isset($attachment['pretext'])) {
                    $formatted[] = $this->formatText($attachment['pretext']);
                }
                if (isset($attachment['fallback'])) {
                    $formatted[] = $this->formatText($attachment['fallback']);
                }
            }
        }
        return $formatted;
    }

    public function formatText(string $text) : string
    {
        $pattern = '/<([^>]*)>/';

        preg_match_all($pattern, $text, $matches);

        $split = preg_split($pattern, $text);

        $index = 0;
        $formatted = [];

        foreach ($matches[1] as $match) {
            $formatted[] = $split[$index++];
            $formatted[] = $this->formatEntity($match);
        }

        if ($index < count($split)) {
            $formatted[] = $split[$index];
        }

        return trim(implode('', array_filter($formatted)));
    }

    public function formatEntity(string $entity) : string
    {
        $pipe = strrpos($entity, '|');

        $readable = ($pipe !== false) ? substr($entity, $pipe + 1) : null;

        if ($entity && $entity[0] === '@') {
            return $this->formatUser($entity, $readable);
        }

        if ($entity && $entity[0] === '#') {
            return $this->formatChannel($entity, $readable);
        }

        return $readable ? $readable : $entity;
    }

    public function formatUser(string $entity, $readable = null) : string
    {
        if ($readable) {
            return "@$readable";
        }

        $userId = substr($entity, 1);
        if ($user = $this->getUserById($userId)) {
            return '@'.$user->getUsername();
        }

        return $entity;
    }

    public function formatChannel(string $entity, $readable = null) : string
    {
        if ($readable) {
            return "#$readable";
        }

        $channelId = substr($entity, 1);
        if ($channel = $this->getChannelById($channelId)) {
            return '#'.$channel->getName();
        }

        return $entity;
    }

    protected function isMessageChanged(array $data): bool
    {
        return isset($data['subtype']) && $data['subtype'] === 'message_changed';
    }
}
