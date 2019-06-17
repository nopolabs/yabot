<?php

namespace Nopolabs\Yabot\Message;

use Exception;
use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Helpers\SlackTrait;
use Nopolabs\Yabot\Slack\Client;
use Psr\Log\LoggerInterface;
use Slack\Channel;

class MessageFactory
{
    use SlackTrait;
    use LogTrait;

    public function __construct(Client $slackClient, LoggerInterface $logger = null)
    {
        $this->setSlack($slackClient);
        $this->setLog($logger);
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
        if ($userId = $this->getUserId($data)) {
            return $this->getUserById($userId);
        }

        $this->warning("Cannot find user for $userId, updating users for future reference.");
        $this->getSlack()->updateUsers();

        return null;
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
        if ($this->isMessageChanged($data, 'text')) {
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
                $formatted[] = $this->formatAttachmentField($attachment, 'pretext');
                $formatted[] = $this->formatAttachmentField($attachment, 'fallback');
            }
        }
        return array_values(array_filter($formatted));
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

    protected function isMessageChanged(array $data, $param = null): bool
    {
        return isset($data['subtype'])
            && $data['subtype'] === 'message_changed'
            && ($param ? isset($data['message'][$param]) : true);
    }

    protected function getUserId(array $data)
    {
        if ($this->isMessageChanged($data, 'user')) {
            return $data['message']['user'];
        }

        if (isset($data['user'])) {
            return $data['user'];
        }

        return null;
    }

    protected function formatAttachmentField(array $attachment, string $field)
    {
        return isset($attachment[$field])
            ? $this->formatText($attachment[$field])
            : null;
    }
}
