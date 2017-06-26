<?php
namespace Nopolabs\Tests\Yabot\Plugin;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\MessageLogPlugin;
use Nopolabs\Yabot\Yabot;
use PHPUnit\Framework\TestCase;

class MessageLogPluginTest extends TestCase
{
    use MockWithExpectationsTrait;

    private $yabot;
    private $plugin;

    protected function setUp()
    {
        $this->yabot = $this->createMock(Yabot::class);
        $this->plugin = new MessageLogPlugin($this->yabot);
    }

    public function testStatus_logging()
    {
        $this->setAtExpectations($this->yabot, [
            ['getMessageLog', ['result' => 'message.log']],
        ]);

        $this->assertEquals('logging messages in message.log', $this->plugin->status());
    }

    public function testStatus_not_logging()
    {
        $this->setAtExpectations($this->yabot, [
            ['getMessageLog', ['result' => null]],
        ]);

        $this->assertEquals('not logging messages', $this->plugin->status());
    }

    public function testStart()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setAtExpectations($this->yabot, [
            ['setMessageLog', ['params' => ['message.log']]],
        ]);

        $this->plugin->start($msg);
    }

    public function testStop()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setAtExpectations($this->yabot, [
            ['setMessageLog', ['params' => null]],
        ]);

        $this->plugin->stop($msg);
    }
}