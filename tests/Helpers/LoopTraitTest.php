<?php
namespace Nopolabs\Yabot\Tests\Helpers;

use Closure;
use Exception;
use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Helpers\LoopTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class LoopTraitTest extends TestCase
{
    use MockWithExpectationsTrait;
    use LogTrait;
    use LoopTrait;

    protected function setUp()
    {
        $this->setLog($this->createMock(LoggerInterface::class));
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

    public function testCallable()
    {
        $called = false;

        $function = function() use (&$called) {
            $called = true;
            return;
        };

        $callable = $this->callable($function);

        $callable();

        $this->assertTrue($called);
    }

    public function testCallableHandlesException()
    {
        $this->setAtExpectations($this->log, [
            ['warning', ['params' => [$this->callback(function($errmsg) {
                $this->assertRegExp("/^Unhandled Exception in Timer callback\ntesting\n#0/m", $errmsg);
                return true;
            })]]],
        ]);

        $function = function() {
            throw new Exception('testing');
        };

        $callable = $this->callable($function);

        $callable();
    }
}
