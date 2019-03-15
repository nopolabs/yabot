<?php
namespace Nopolabs\Yabot\Tests\Guzzle;


use Nopolabs\Yabot\Guzzle\GuzzleFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Guzzle\Guzzle;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class GuzzleTest extends TestCase
{
    use MockWithExpectationsTrait;

    /** @var LoopInterface */
    private $eventLoop;

    /** @var Guzzle */
    private $guzzle;

    /** @var Client */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    protected function setUp()
    {
        $this->eventLoop = Factory::create();

        $guzzleFactory = new GuzzleFactory($this->eventLoop);

        $this->client = $guzzleFactory->newClient(['timeout' => 2]);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->guzzle = new Guzzle($this->client, $this->logger);

        $this->eventLoop->run();
    }

    protected function tearDown()
    {
        $this->eventLoop->stop();
    }

    public function testGetClient()
    {
        $this->assertSame($this->client, $this->guzzle->getClient());
    }

    /**
     * This is a 'functional' test.
     * It makes an http get request to http://nopolabs.com.
     */
    public function testGetAsync()
    {
        $status = null;
        $exception = null;

        $this->guzzle->getAsync(
            'http://nopolabs.com',
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

    /**
     * This is a 'functional' test.
     * It makes an http get request to https://slowly.nopolabs.com/data/3000/hello
     */
    public function testGetAsyncTimeout()
    {
        $this->setAtExpectations($this->logger, [
            ['warning', ['params' => [$this->callback(function($message) {
                $this->assertStringStartsWith('cURL error 28: Operation timed out', $message);
                return true;
            })]]],
        ]);

        $status = null;

        $this->guzzle->getAsync('https://slowly.nopolabs.com/data/3000/hello')->then(
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

    public function testGet()
    {
        $response = $this->createMock(ResponseInterface::class);

        /** @var Client $client */
        $client = $this->newPartialMockWithExpectations(Client::class, [
            ['get', [
                'params' => ['http://nopolabs.com', ['params' => 'data']],
                'result' => $response,
            ]],
        ]);

        $guzzle = new Guzzle($client);

        $actual = $guzzle->get('http://nopolabs.com', ['params' => 'data']);

        $this->assertSame($response, $actual);
    }

    public function testPut()
    {
        $response = $this->createMock(ResponseInterface::class);

        /** @var Client $client */
        $client = $this->newPartialMockWithExpectations(Client::class, [
            ['put', [
                'params' => ['http://nopolabs.com', ['body' => 'raw data']],
                'result' => $response,
            ]],
        ]);

        $guzzle = new Guzzle($client);

        $actual = $guzzle->put('http://nopolabs.com', ['body' => 'raw data']);

        $this->assertSame($response, $actual);
    }

    public function testPost()
    {
        $response = $this->createMock(ResponseInterface::class);

        /** @var Client $client */
        $client = $this->newPartialMockWithExpectations(Client::class, [
            ['post', [
                'params' => ['http://nopolabs.com', ['body' => 'raw data']],
                'result' => $response,
            ]],
        ]);

        $guzzle = new Guzzle($client);

        $actual = $guzzle->post('http://nopolabs.com', ['body' => 'raw data']);

        $this->assertSame($response, $actual);
    }
}