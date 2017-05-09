<?php

namespace Nopolabs\Yabot\Tests\Guzzle;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Nopolabs\Yabot\Guzzle\Guzzle;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class GuzzleTest extends TestCase
{
    /** @var LoopInterface */
    private $eventLoop;

    /** @var Client */
    private $guzzle;

    protected function setUp()
    {
        $this->eventLoop = Factory::create();
        $this->guzzle = new Guzzle($this->eventLoop, ['timeout' => 5]);

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

        $this->guzzle->getAsync('http://nopolabs.com')->then(
            function (ResponseInterface $res) use (&$status) {
                $status = $res->getStatusCode();
            },
            function (RequestException $e) use (&$exception) {
                $exception = $e;
            }
        );

        $timer = $this->eventLoop->addTimer(6, function() {});

        while ($this->eventLoop->isTimerActive($timer)) {
            $this->eventLoop->tick();
            if ($status || $exception) {
                break;
            }
        }

        $this->assertTrue($status || $exception);
    }
}