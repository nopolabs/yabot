<?php

namespace Nopolabs\Yabot\Bot;


use Nopolabs\Yabot\Helpers\SlackTrait;

class TextFormatter
{
    use SlackTrait;

    public function __construct(SlackClient $slack)
    {
        $this->setSlack($slack);
    }

    public function formatText(string $text) : string
    {
        $pattern = '/<([^>]*)>/';

        preg_match_all($pattern, $text, $matches);

        $split = preg_split($pattern, $text);

        $i = 0;
        $formatted = [];

        foreach ($matches[1] as $match) {
            $formatted[] = $split[$i++];
            if ($pipe = strrpos($match, '|')) {
                $fallback = substr($match, $pipe + 1);
                $formatted[] = $this->formatFallback($match, $fallback);
            } else {
                $formatted[] = $this->formatNoFallback($match);
            }
        }

        if ($i < count($split)) {
            $formatted[] = $split[$i];
        }

        return trim(implode('', $formatted));
    }

    private function formatFallback($match, $fallback)
    {
        if ($match[0] === '@') {
            return '@'.$fallback;
        }

        if ($match[0] === '#') {
            return '#'.$fallback;
        }

        return $fallback;
    }

    private function formatNoFallback($match)
    {
        if ($match[0] === '@') {
            return $this->formatUser($match);
        }

        if ($match[0] === '#') {
            return $this->formatChannel($match);
        }

        return $match;
    }

    private function formatUser($match)
    {
        $userId = substr($match, 1);
        if ($user = $this->getSlack()->userById($userId)) {
            return '@'.$user->getUsername();
        }

        return '@'.$userId;
    }

    private function formatChannel($match)
    {
        $channelId = substr($match, 1);
        if ($channel = $this->getSlack()->channelById($channelId)) {
            return '#'.$channel->getName();
        }

        return '#'.$channelId;
    }
}