<?php

namespace Nopolabs\Yabot\Tests\Helpers;


use GuzzleHttp\Exception\RequestException;
use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Guzzle\Guzzle;
use Nopolabs\Yabot\Guzzle\GuzzleFactory;
use Nopolabs\Yabot\Helpers\GuzzleTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class GuzzleTraitFunctionalTest extends TestCase
{
    use MockWithExpectationsTrait;
    use GuzzleTrait;

    /** @var LoopInterface */
    private $eventLoop;

    /** @var LoggerInterface */
    private $logger;

    protected function setUp()
    {
        $this->eventLoop = Factory::create();

        $guzzleFactory = new GuzzleFactory($this->eventLoop);

        $client = $guzzleFactory->newClient(['timeout' => 2]);

        $this->logger = $this->createMock(LoggerInterface::class);

        $guzzle = new Guzzle($client, $this->logger);

        $this->setGuzzle($guzzle);

        $this->eventLoop->run();
    }

    protected function tearDown()
    {
        $this->eventLoop->stop();
    }

    public function testGetAsync()
    {
        $status = null;
        $exception = null;

        $this->getGuzzle()->getAsync(
            'https://slowly.nopolabs.com/1000/data/hello',
            [],
            function (RequestException $e) use (&$exception) {
                $exception = $e;
            }
        )->then(
            function (ResponseInterface $res) use (&$status) {
                $status = $res->getStatusCode();
            }
        );

        $timer = $this->eventLoop->addTimer(5, function() {});

        while ($this->eventLoop->isTimerActive($timer)) {

            // tick() to allow periodic timer set by Guzzle::scheduleProcessing() to run.
            $this->eventLoop->tick();

            if ($status || $exception) {
                break;
            }
        }

        $this->assertTrue($status || $exception);
    }

    public function testGetAsyncTimeout()
    {
        $this->setAtExpectations($this->logger, [
            ['warning', ['params' => [$this->callback(function($message) {
                $this->assertStringStartsWith('cURL error 28: Operation timed out', $message);
                return true;
            })]]],
        ]);

        $status = null;

        $this->getGuzzle()->getAsync('https://slowly.nopolabs.com/3000/data/hello')->then(
            function (ResponseInterface $res) use (&$status) {
                $status = $res->getStatusCode();
            }
        );

        $timer = $this->eventLoop->addTimer(5, function() {});

        while ($this->eventLoop->isTimerActive($timer)) {

            // tick() to allow periodic timer set by Guzzle::scheduleProcessing() to run.
            $this->eventLoop->tick();

            if ($status) {
                break;
            }
        }

        $this->assertNull($status);
    }
}