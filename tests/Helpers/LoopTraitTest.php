<?php
namespace Nopolabs\Yabot\Tests\Helpers;

use Closure;
use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Helpers\LoopTrait;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class LoopTraitTest extends TestCase
{
    use MockWithExpectationsTrait;
    use LoopTrait;

    protected function setUp()
    {
        $this->setLoop($this->createMock(LoopInterface::class));
    }

    public function testAddTimer()
    {
        $this->setAtExpectations($this->loop, [
            ['addTimer', ['params' => [60, $this->isInstanceOf(Closure::class)]]],
        ]);

        $this->addTimer(60, 'phpinfo');
    }

    public function testAddPeriodicTimer()
    {
        $this->setAtExpectations($this->loop, [
            ['addPeriodicTimer', ['params' => [60, $this->isInstanceOf(Closure::class)]]],
        ]);

        $this->addPeriodicTimer(60, 'phpinfo');
    }

    public function testCancelTimer()
    {
        $timer = $this->createMock(TimerInterface::class);

        $this->setAtExpectations($this->loop, [
            ['cancelTimer', ['params' => [$timer]]],
        ]);

        $this->cancelTimer($timer);
    }
}