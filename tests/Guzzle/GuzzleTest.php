<?php
namespace Nopolabs\Yabot\Tests\Guzzle;


use Nopolabs\Yabot\Guzzle\GuzzleFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlMultiHandler;
use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Guzzle\Guzzle;
use Nopolabs\Yabot\Guzzle\ReactAwareCurlFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
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

    protected function setUp()
    {
        $this->eventLoop = Factory::create();
        $this->client = GuzzleFactory::newClient($this->eventLoop, ['timeout' => 5]);

        $this->guzzle = new Guzzle($this->client);

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

        $this->guzzle->getAsync('http://nopolabs.com')->then(
            function (ResponseInterface $res) use (&$status) {
                $status = $res->getStatusCode();
            },
            function (RequestException $e) use (&$exception) {
                $exception = $e;
            }
        );

        $timer = $this->eventLoop->addTimer(10, function() {});

        while ($this->eventLoop->isTimerActive($timer)) {

            // tick() to allow periodic timer set by Guzzle::scheduleProcessing() to run.
            $this->eventLoop->tick();

            if ($status || $exception) {
                break;
            }
        }

        $this->assertTrue($status || $exception);
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

        $handler = $this->createMock(CurlMultiHandler::class);

        $eventLoop = $this->createMock(LoopInterface::class);

        $guzzle = new Guzzle($client, $handler, $eventLoop);

        $actual = $guzzle->post('http://nopolabs.com', ['body' => 'raw data']);

        $this->assertSame($response, $actual);
    }
}