<?php

namespace Nopolabs\Yabot\Tests\Slack;


use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Slack\Bots;
use PHPUnit\Framework\TestCase;
use Slack\Bot;

class BotsTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function testUsers()
    {
        $bot = $this->newPartialMockWithExpectations(Bot::class, [
            ['getId', ['result' => 'B00ARTOO0']],
            ['getName', ['result' => 'Artoo']],
        ]);

        $bots = new Bots();

        $this->assertNull($bots->byId('B00ARTOO0'));
        $this->assertNull($bots->byId('B00UNKN00'));
        $this->assertNull($bots->byName('Artoo'));
        $this->assertNull($bots->byId('Unknown'));

        $bots->update([$bot]);

        $this->assertSame($bot, $bots->byId('B00ARTOO0'));
        $this->assertNull($bots->byId('B00UNKN00'));
        $this->assertSame($bot, $bots->byName('Artoo'));
        $this->assertNull($bots->byId('Unknown'));
    }
}