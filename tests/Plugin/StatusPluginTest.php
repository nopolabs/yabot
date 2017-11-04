<?php
namespace Nopolabs\Tests\Yabot\Plugin;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\StatusPlugin;
use Nopolabs\Yabot\Slack\Client;
use Nopolabs\Yabot\Yabot;
use PHPUnit\Framework\TestCase;

class StatusPluginTest extends TestCase
{
    use MockWithExpectationsTrait;

    private $slack;
    private $yabot;

    /** @var StatusPlugin */
    private $plugin;

    protected function setUp()
    {
        $this->slack = $this->createMock(Client::class);
        $this->yabot = $this->createMock(Yabot::class);

        $this->setExpectations($this->yabot, [
            'getSlack' => ['invoked' => $this->any(), 'result' => $this->slack],
        ]);

        $this->plugin = new StatusPlugin($this->yabot);
    }

    public function testYabotStatus()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['reply', ['params' => ['Everything is OK!']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setExpectations($this->yabot, [
            'getStatus' => ['result' => 'Everything is OK!'],
        ]);

        $this->plugin->yabotStatus($msg);
    }

    public function testYabotShutdown()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setExpectations($this->yabot, [
            'shutDown' => ['params' => []],
        ]);

        $this->plugin->yabotShutdown($msg);
    }

    public function testYabotReconnect()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setExpectations($this->yabot, [
            'reconnect' => ['params' => []],
        ]);

        $this->plugin->yabotReconnect($msg);
    }

    public function testCountUsers()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['reply', ['params' => [1]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setExpectations($this->slack, [
            'getUsersMap' => ['result' => [[
                'id' => 'name',
            ]]],
        ]);

        $this->plugin->countUsers($msg);
    }

    public function testCountBots()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['reply', ['params' => [1]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setExpectations($this->slack, [
            'getBotsMap' => ['result' => [[
                'id' => 'name',
            ]]],
        ]);

        $this->plugin->countBots($msg);
    }

    public function testCountChannels()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['reply', ['params' => [1]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setExpectations($this->slack, [
            'getChannelsMap' => ['result' => [[
                'id' => 'name',
            ]]],
        ]);

        $this->plugin->countChannels($msg);
    }

    public function testListUsers()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['reply', ['params' => ['{"id":"name","id2":"name2"}']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setExpectations($this->slack, [
            'getUsersMap' => ['result' => [
                'id' => 'name',
                'id2' => 'name2',
            ]],
        ]);

        $this->plugin->listUsers($msg);
    }

    public function testListBots()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['reply', ['params' => ['{"id":"name","id2":"name2"}']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setExpectations($this->slack, [
            'getBotsMap' => ['result' => [
                'id' => 'name',
                'id2' => 'name2',
            ]],
        ]);

        $this->plugin->listBots($msg);
    }

    public function testListChannels()
    {
        $msg = $this->newPartialMockWithExpectations(Message::class, [
            ['reply', ['params' => ['{"id":"name","id2":"name2"}']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->setExpectations($this->slack, [
            'getChannelsMap' => ['result' => [
                'id' => 'name',
                'id2' => 'name2',
            ]],
        ]);

        $this->plugin->listChannels($msg);
    }
}