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
        $user = $this->createMock(User::class);

        $getUserById = $userId ? ['params' => [$userId], 'result' => $user] : 'never';

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

    public function assembleFormattedTestDataProvider()
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
                ['neopolitan', 'tasty'],
                "[neopolitan]\n[tasty]"
            ],
            [
                ['text' => 'neopolitan', 'attachments' => [
                    ['fallback' => 'vanilla'],
                    ['fallback' => 'chocolate'],
                    ['fallback' => 'strawberry'],
                ]],
                ['neopolitan', 'vanilla', 'chocolate', 'strawberry'],
                "[neopolitan]\n[vanilla]\n[chocolate]\n[strawberry]"
            ],
        ];
    }

    /**
     * @dataProvider assembleFormattedTestDataProvider
     */
    public function testAssembleFormattedText(array $data, array $texts, $expected)
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

        $formattedText = $factory->assembleFormattedText($data);

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
            ['', false, 'formatReadable'],
            ['http://opensky.com', false, 'formatReadable'],
            ['http://opensky.com|OpenSky', 'OpenSky', 'formatReadable'],
            ['@U00000000', false, 'formatUser'],
            ['@U00000000|alice', 'alice', 'formatUser'],
            ['#C00000000', false, 'formatChannel'],
            ['#C00000000|general', 'general', 'formatChannel'],
        ];
    }

    /**
     * @dataProvider formatEntityDataProvider
     */
    public function testFormatEntity($entity, $readable, $formatMethod)
    {
        $factory = $this->newMessageFactory([
            [$formatMethod, ['params' => [$entity, $readable], 'result' => 'expected']],
        ]);

        $formatted = $factory->formatEntity($entity);

        $this->assertEquals('expected', $formatted);
    }

    public function formatUserDataProvider()
    {
        return [
            ['@alice', false, false, '@alice'],
            ['@U00000000|Alice', 'Alice', false, 'Alice'],
            ['@U00000000|Alice', 'Alice', true, 'Alice'],
            ['@U00000000', false, true, '@user-name'],
            ['@U00000000', false, false, '@U00000000'],
        ];
    }

    /**
     * @dataProvider formatUserDataProvider
     */
    public function testFormatUser($entity, $readable, $knownUser, $expected)
    {
        if (!$readable && $knownUser) {
            $user = $this->newPartialMockWithExpectations(User::class, [
                ['getUsername', ['result' => 'user-name']],
            ]);
            $factory = $this->newMessageFactory([
                ['getUserById', ['params' => [substr($entity, 1)], 'result' => $user]],
            ]);
        } else {
            $factory = $this->newMessageFactory();
        }

        $formatted = $factory->formatUser($entity, $readable);

        $this->assertEquals($expected, $formatted);
    }

    public function formatChannelDataProvider()
    {
        return [
            ['#general', false, false, '#general'],
            ['#C00000000|general', 'general', false, 'general'],
            ['#C00000000|general', 'general', true, 'general'],
            ['#C00000000', false, true, '#channel-name'],
            ['#C00000000', false, false, '#C00000000'],
        ];
    }

    /**
     * @dataProvider formatChannelDataProvider
     */
    public function testFormatChannel($entity, $readable, $knownChannel, $expected)
    {
        if (!$readable && $knownChannel) {
            $channel = $this->newPartialMockWithExpectations(Channel::class, [
                ['getName', ['result' => 'channel-name']],
            ]);
            $factory = $this->newMessageFactory([
                ['getChannelById', ['params' => [substr($entity, 1)], 'result' => $channel]],
            ]);
        } else {
            $factory = $this->newMessageFactory();
        }

        $formatted = $factory->formatChannel($entity, $readable);

        $this->assertEquals($expected, $formatted);
    }

    public function testFormatReadable()
    {
        $factory = $this->newMessageFactory();

        $this->assertEquals('readable', $factory->formatReadable('entity|readable', 'readable'));
        $this->assertEquals('entity', $factory->formatReadable('entity', 'entity'));
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