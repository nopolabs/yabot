<?php

namespace Message;


use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\MessageFactory;
use Nopolabs\Yabot\Slack\Client;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Exception;
use Slack\Channel;
use Slack\User;

class MessageFactoryTest extends TestCase
{
    use MockWithExpectationsTrait;

    private $slackClient;

    protected function setUp()
    {
        $this->slackClient = $this->createMock(Client::class);
    }

    public function testCreate()
    {
        $data = ['text' => 'dummy-text'];

        $user = $this->createMock(User::class);
        $channel = $this->createMock(Channel::class);

        $factory = $this->newMessageFactory([
            ['assembleFormattedText', ['params' => [$data], 'result' => 'formatted-text']],
            ['getUser', ['params' => [$data], 'result' => $user]],
            ['getChannel', ['params' => [$data], 'result' => $channel]],
        ]);

        $message = $factory->create($data);

        $this->assertSame('dummy-text', $message->getText());
        $this->assertSame('formatted-text', $message->getFormattedText());
        $this->assertSame($user, $message->getUser());
        $this->assertSame($channel, $message->getChannel());
    }

    public function userDataProvider() : array
    {
        $data = [
            [[], null],
            [['user' => 'user-id'], 'user-id'],
            [['user' => 'user-id', 'subtype' => 'foobar'], 'user-id'],
            [['user' => 'user-id', 'subtype' => 'message_changed'], 'user-id'],
            [['user' => 'user-id', 'subtype' => 'message_changed', 'message' => ['user' => 'user2-id']], 'user2-id'],
        ];

        return array_slice($data, 0, 100);
    }

    /**
     * @dataProvider userDataProvider
     */
    public function testGetUser(array $data, $userId)
    {
        $user = $userId ? $this->createMock(User::class) : null;

        $getUserById = ['params' => [$userId], 'result' => $user];

        $factory = $this->newMessageFactory([
            ['getUserById', $getUserById],
        ]);

        $actual = $factory->getUser($data);

        if ($userId) {
            $this->assertSame($user, $actual);
        } else {
            $this->assertNull($actual);
        }
    }

    public function testGetChannel()
    {
        $channel = $this->createMock(Channel::class);

        $factory = $this->newMessageFactory([
            ['getChannelById', ['params' => ['channel-id'], 'result' => $channel]],
        ]);

        $actual = $factory->getChannel(['channel' => 'channel-id']);

        $this->assertSame($channel, $actual);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Message data does not have a channel.
     */
    public function testGetChannelException()
    {
        $factory = $this->newMessageFactory([
            ['getChannelById', ['params' => ['channel-id'], 'result' => null]],
        ]);

        $factory->getChannel(['channel' => 'channel-id']);
    }

    public function testAssembleFormattedText()
    {
        $data = ['dummy' => 'data'];

        $factory = $this->newMessageFactory([
            ['formatMessageText', ['params' => [$data], 'result' => 'formatted message text']],
            ['formatAttachmentsText', ['params' => [$data], 'result' => ['A', '', 'B']]],
        ]);

        $formattedText = $factory->assembleFormattedText($data);

        $this->assertEquals("formatted message text\nA\nB", $formattedText);
    }

    public function messageDataProvider()
    {
        return [
            [[], [], ''],
            [
                ['text' => 'vanilla'],
                ['vanilla'],
                '[vanilla]'
            ],
            [
                ['subtype' => 'bot_message', 'text' => 'vanilla'],
                ['vanilla'],
                '[vanilla]'
            ],
            [
                ['subtype' => 'message_changed', 'message' => ['text' => 'chocolate']],
                ['chocolate'],
                '[chocolate]'
            ],
            [
                ['text' => 'neopolitan', 'attachments' => []],
                ['neopolitan'],
                '[neopolitan]'
            ],
            [
                ['text' => 'neopolitan', 'attachments' => [['pretext' => 'tasty']]],
                ['neopolitan'],
                '[neopolitan]'
            ],
            [
                ['text' => 'neopolitan', 'attachments' => [
                    ['fallback' => 'vanilla'],
                    ['fallback' => 'chocolate'],
                    ['fallback' => 'strawberry'],
                ]],
                ['neopolitan'],
                '[neopolitan]'
            ],
        ];
    }

    /**
     * @dataProvider messageDataProvider
     */
    public function testFormatMessageText(array $data, array $texts, $expected)
    {
        $expectations = array_map(function ($text) {
            return ['formatText', ['params' => [$text], 'result' => "[$text]"]];
        }, $texts);

        if ($expectations) {
            $factory = $this->newMessageFactory($expectations);
            $factory->expects($this->exactly(count($expectations)))->method('formatText');
        } else {
            $factory = $this->newMessageFactory();
        }

        $formattedText = $factory->formatMessageText($data);

        $this->assertEquals($expected, $formattedText);
   }

    public function attachmentsDataProvider()
    {
        return [
            [[], [], []],
            [
                ['text' => 'vanilla'], [], [],
            ],
            [
                ['subtype' => 'bot_message', 'text' => 'vanilla'], [], [],
            ],
            [
                ['subtype' => 'message_changed', 'message' => ['text' => 'chocolate']], [], [],
            ],
            [
                ['text' => 'neopolitan', 'attachments' => []], [], [],
            ],
            [
                ['text' => 'neopolitan', 'attachments' => [['pretext' => 'tasty']]],
                ['tasty'],
                ['[tasty]'],
            ],
            [
                ['text' => 'neopolitan', 'attachments' => [
                    ['fallback' => 'vanilla'],
                    ['fallback' => 'chocolate'],
                    ['fallback' => 'strawberry'],
                ]],
                ['vanilla', 'chocolate', 'strawberry'],
                ['[vanilla]', '[chocolate]', '[strawberry]'],
            ],
        ];
    }

    /**
     * @dataProvider attachmentsDataProvider
     */
    public function testFormatAttachmentsText(array $data, array $texts, $expected)
    {
        $expectations = array_map(function ($text) {
            return ['formatText', ['params' => [$text], 'result' => "[$text]"]];
        }, $texts);

        if ($expectations) {
            $factory = $this->newMessageFactory($expectations);
            $factory->expects($this->exactly(count($expectations)))->method('formatText');
        } else {
            $factory = $this->newMessageFactory();
        }

        $formattedText = $factory->formatAttachmentsText($data);

        $this->assertEquals($expected, $formattedText);
    }

    public function formatTextDataProvider()
    {
        return [
            ['', [], ''],
            ['hello world', [], 'hello world'],
            ['hello @world', [], 'hello @world'],
            ['hello <@world>', ['@world'], 'hello [@world]'],
            ['hello <@alice> and <@bob>', ['@alice', '@bob'], 'hello [@alice] and [@bob]'],
            ['visit <http://nopolabs.com>', ['http://nopolabs.com'], 'visit [http://nopolabs.com]'],
        ];
    }

    /**
     * @dataProvider formatTextDataProvider
     */
    public function testFormatText($text, array $matches, $expected)
    {
        $expectations = array_map(function ($match) {
            return ['formatEntity', ['params' => [$match], 'result' => "[$match]"]];
        }, $matches);

        if ($expectations) {
            $factory = $this->newMessageFactory($expectations);
            $factory->expects($this->exactly(count($expectations)))->method('formatEntity');
        } else {
            $factory = $this->newMessageFactory();
        }

        $formatted = $factory->formatText($text);

        $this->assertEquals($expected, $formatted);
    }

    public function formatEntityDataProvider()
    {
        return [
            ['', ''],
            ['http://nopolabs.com', 'http://nopolabs.com'],
            ['http://nopolabs.com|Nopo Labs', 'Nopo Labs'],
            ['@U00000000', '@known-user'],
            ['@U00000000|alice', '@alice'],
            ['#C00000000', '#known-channel'],
            ['#C00000000|general', '#general'],
        ];
    }

    /**
     * @dataProvider formatEntityDataProvider
     */
    public function testFormatEntity($entity, $expected)
    {
        $user = $this->newPartialMockWithExpectations(User::class, [
            'getUsername' => ['invoked' => 'any', 'result' => 'known-user'],
        ]);

        $channel = $this->newPartialMockWithExpectations(Channel::class, [
            'getName' => ['invoked' => 'any', 'result' => 'known-channel'],
        ]);

        $factory = $this->newMessageFactory([
            'getUserById' => ['invoked' => 'any', 'params' => ['U00000000'], 'result' => $user],
            'getChannelById' => ['invoked' => 'any', 'params' => ['C00000000'], 'result' => $channel],
        ]);

        $formatted = $factory->formatEntity($entity);

        $this->assertEquals($expected, $formatted);
    }

    public function formatUserDataProvider()
    {
        return [
            ['@U00000000', null, '@known-user'],
            ['@U00000000', 'alice', '@alice'],
            ['@unknown-user', null, '@unknown-user'],
            ['@unknown-user', 'bob', '@bob'],
        ];
    }

    /**
     * @dataProvider formatUserDataProvider
     */
    public function testFormatUser($userId, $readable, $expected)
    {
        $user = $this->newPartialMockWithExpectations(User::class, [
            'getUsername' => ['invoked' => 'any', 'result' => 'known-user'],
        ]);

        $factory = $this->newMessageFactory([
            'getUserById' => [
                'invoked' => 'any',
                'params' => [substr($userId, 1)],
                'result' => function ($userId) use ($user) {
                    return ($userId === 'U00000000') ? $user : null;
                },
            ],
        ]);

        $formatted = $factory->formatUser($userId, $readable);

        $this->assertEquals($expected, $formatted);
    }

    public function formatChannelDataProvider()
    {
        return [
            ['#C00000000', null, '#known-channel'],
            ['#C00000000', 'general', '#general'],
            ['#unknown-channel', null, '#unknown-channel'],
            ['#unknown-channel', 'special', '#special'],
        ];
    }

    /**
     * @dataProvider formatChannelDataProvider
     */
    public function testFormatChannel($channelId, $readable, $expected)
    {
        $channel = $this->newPartialMockWithExpectations(Channel::class, [
            'getName' => ['invoked' => 'any', 'result' => 'known-channel'],
        ]);
        $factory = $this->newMessageFactory([
            'getChannelById' => [
                'invoked' => 'any',
                'params' => [substr($channelId, 1)],
                'result' => function ($channelId) use ($channel) {
                    return ($channelId === 'C00000000') ? $channel : null;
                },
            ],
        ]);

        $formatted = $factory->formatChannel($channelId, $readable);

        $this->assertEquals($expected, $formatted);
    }

    private function newMessageFactory(array $expectations = [])
    {
        if (empty($expectations)) {
            return new MessageFactory($this->slackClient);
        }

        return $this->newPartialMockWithExpectations(
            MessageFactory::class,
            $expectations,
            [$this->slackClient]
        );
    }
}