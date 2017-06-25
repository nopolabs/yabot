<?php
namespace Nopolabs\Tests\Yabot\Plugin;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\HelpPlugin;
use Nopolabs\Yabot\Yabot;
use PHPUnit\Framework\TestCase;

class HelpPluginTest extends TestCase
{
    use MockWithExpectationsTrait;

    private $yabot;
    private $plugin;

    protected function setUp()
    {
        $this->yabot = $this->createMock(Yabot::class);
        $this->plugin = new HelpPlugin($this->yabot);
    }

    public function testYabotHelp()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['reply', ['params' => ['Help! I am trapped.']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setAtExpectations($this->yabot, [
            ['getHelp', ['result' => 'Help! I am trapped.']],
        ]);

        $this->plugin->yabotHelp($msg);
    }
}