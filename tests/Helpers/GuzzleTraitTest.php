<?php
namespace Nopolabs\Yabot\Tests\Helpers;

use GuzzleHttp\Promise\PromiseInterface;
use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Guzzle\Guzzle;
use Nopolabs\Yabot\Helpers\GuzzleTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class GuzzleTraitTest extends TestCase
{
    use MockWithExpectationsTrait;
    use GuzzleTrait;

    private $mockGuzzle;

    protected function setUp()
    {
        $this->mockGuzzle = $this->createMock(Guzzle::class);
    }

    public function testGetSetGuzzle()
    {
        $this->setGuzzle($this->mockGuzzle);

        $this->assertSame($this->mockGuzzle, $this->getGuzzle());
    }

    public function testGetSetOptions()
    {
        $this->assertEquals([], $this->getOptions());

        $this->setOptions(['timeout' => 5]);

        $this->assertSame(['timeout' => 5], $this->getOptions());
    }

    public function testGetAsync()
    {
        $this->setGuzzle($this->mockGuzzle);
        $this->setOptions(['timeout' => 5]);

        $promise = $this->createMock(PromiseInterface::class);

        $this->setAtExpectations($this->mockGuzzle, [
            ['getAsync', [
                'params' => [
                    'http://example.com',
                    [
                        'query' => ['foo' => 'bar'],
                        'timeout' => 5,
                    ]],
                'result' => $promise,
            ]],
        ]);

        $actual = $this->getAsync('http://example.com', ['query' => ['foo' => 'bar']]);

        $this->assertSame($promise, $actual);
    }

    public function testPost()
    {
        $this->setGuzzle($this->mockGuzzle);
        $this->setOptions(['timeout' => 5]);

        $response = $this->createMock(ResponseInterface::class);

        $this->setAtExpectations($this->mockGuzzle, [
            ['post', [
                'params' => [
                    'http://example.com',
                    [
                        'body' => 'data',
                        'timeout' => 5,
                    ]],
                'result' => $response,
            ]],
        ]);

        $actual = $this->post('http://example.com', ['body' => 'data']);

        $this->assertSame($response, $actual);
    }
}