<?php

namespace Bot;


use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Bot\SlackClient;
use Nopolabs\Yabot\Bot\TextFormatter;
use PHPUnit\Framework\TestCase;
use Slack\User;

class TextFormatterTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function formatTextDataProvider() : array
    {
        $data = [
            ['', ''],
            ['test', 'test'],
            ['<@U00UNKN00>', '@U00UNKN00'], // unknown user
            ['<@U00UNKN00|alice>', '@alice'], // unknown user w/fallback
            ['<@U00USER00>', '@user-name'], // known user
            ['<@U00USER00|nick>', '@nick'], // known user w/fallback
            ['<#C00UNKN00>', '#C00UNKN00'], // unknown channel
            ['<#C00UNKN00|channel-x>', '#channel-x'], // unknown channel w/fallback
            ['<#C00CHAN00>', '#channel-name'], // known channel
            ['<#C00CHAN00|good-times>', '#good-times'], // known channel w/fallback
            ['choose <@U00USER00> or <@U00USER00|nick>', 'choose @user-name or @nick'],
        ];

        return array_slice($data, 10, 100);
    }

    /**
     * @dataProvider formatTextDataProvider
     */
    public function testFormatText($text, $expected)
    {
        $user = $this->newPartialMockWithExpectations(User::class, [
            'getUsername' => ['invoked' => 'any', 'result' => 'user-name'],
        ]);

        $channel = $this->newPartialMockWithExpectations(User::class, [
            'getName' => ['invoked' => 'any', 'result' => 'channel-name'],
        ]);

        $slack = $this->newPartialMockWithExpectations(SlackClient::class, [
            'userById' => ['invoked' => 'any', 'result' => function($userId) use ($user) {
                return ($userId === 'U00USER00') ? $user : null;
            }],
            'channelById' => ['invoked' => 'any', 'result' => function($channelId) use ($channel) {
                return ($channelId === 'C00CHAN00') ? $channel : null;
            }],
        ]);

        $formatter = new TextFormatter($slack);

        $formatted = $formatter->formatText($text);

        $this->assertSame($expected, $formatted);
    }
}