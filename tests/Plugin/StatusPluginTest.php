<?php
namespace Nopolabs\Tests\Yabot\Plugin;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\StatusPlugin;
use Nopolabs\Yabot\Yabot;
use PHPUnit\Framework\TestCase;

class StatusPluginTest extends TestCase
{
    use MockWithExpectationsTrait;

    private $yabot;
    private $plugin;

    protected function setUp()
    {
        $this->yabot = $this->createMock(Yabot::class);
        $this->plugin = new StatusPlugin($this->yabot);
    }

    public function testYabotStatus()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['reply', ['params' => ['Everything is OK!']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setAtExpectations($this->yabot, [
            ['getStatus', ['result' => 'Everything is OK!']],
        ]);

        $this->plugin->yabotStatus($msg);
    }
}