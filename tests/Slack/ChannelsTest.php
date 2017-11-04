<?php

namespace Nopolabs\Yabot\Tests\Slack;


use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Slack\Channels;
use PHPUnit\Framework\TestCase;
use Slack\Channel;

class ChannelsTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function testChannels()
    {
        $channel = $this->newPartialMockWithExpectations(Channel::class, [
            'getId' => ['invoked' => $this->any(), 'result' => 'C00CHAN00'],
            'getName' => ['invoked' => $this->any(), 'result' => 'channel-0'],
        ]);

        $channels = new Channels();

        $this->assertNull($channels->byId('C00CHAN00'));
        $this->assertNull($channels->byId('U00UNKN00'));
        $this->assertNull($channels->byName('channel-0'));
        $this->assertNull($channels->byId('Unknown'));

        $channels->update([$channel]);

        $this->assertSame($channel, $channels->byId('C00CHAN00'));
        $this->assertNull($channels->byId('U00UNKN00'));
        $this->assertSame($channel, $channels->byName('channel-0'));
        $this->assertNull($channels->byId('Unknown'));

        $this->assertEquals(['C00CHAN00' => 'channel-0'], $channels->getMap());
    }
}