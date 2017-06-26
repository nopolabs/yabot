<?php
namespace Nopolabs\Yabot\Tests\Helpers;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Helpers\LogTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogTraitTest extends TestCase
{
    use MockWithExpectationsTrait;
    use LogTrait;

    private $logger;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->setLog($this->logger);
    }

    public function testGetLog()
    {
        $this->assertSame($this->logger, $this->getLog());
    }

    public function testEmergency()
    {
        $this->setAtExpectations($this->logger, [
            ['emergency', ['params' => ['emergency!']]],
        ]);

        $this->emergency('emergency!');
    }

    public function testAlert()
    {
        $this->setAtExpectations($this->logger, [
            ['alert', ['params' => ['alert!']]],
        ]);

        $this->alert('alert!');
    }

    public function testCritical()
    {
        $this->setAtExpectations($this->logger, [
            ['critical', ['params' => ['critical!']]],
        ]);

        $this->critical('critical!');
    }

    public function testError()
    {
        $this->setAtExpectations($this->logger, [
            ['error', ['params' => ['error!']]],
        ]);

        $this->error('error!');
    }

    public function testWarning()
    {
        $this->setAtExpectations($this->logger, [
            ['warning', ['params' => ['warning!']]],
        ]);

        $this->warning('warning!');
    }

    public function testNotice()
    {
        $this->setAtExpectations($this->logger, [
            ['notice', ['params' => ['notice!']]],
        ]);

        $this->notice('notice!');
    }

    public function testInfo()
    {
        $this->setAtExpectations($this->logger, [
            ['info', ['params' => ['info!']]],
        ]);

        $this->info('info!');
    }

    public function testDebug()
    {
        $this->setAtExpectations($this->logger, [
            ['debug', ['params' => ['debug!']]],
        ]);

        $this->debug('debug!');
    }

    public function testLog()
    {
        $this->setAtExpectations($this->logger, [
            ['log', ['params' => ['info', 'log!', ['key' => 'value']]]],
        ]);

        $this->log(
            LogLevel::INFO,
            'log!',
            ['key' => 'value']
        );
    }
}