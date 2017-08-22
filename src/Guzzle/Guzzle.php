<?php
namespace Nopolabs\Yabot\Guzzle;


use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Nopolabs\Yabot\Helpers\LogTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Guzzle
{
    use LogTrait;

    /** @var Client */
    private $client;

    public function __construct(Client $client, LoggerInterface $log = null)
    {
        $this->client = $client;
        $this->setLog($log);
    }

    public function getClient() : Client
    {
        return $this->client;
    }

    public function getAsync(string $uri, array $options = [], callable $onRejected = null) : PromiseInterface
    {
        $onRejected = $onRejected ?? function($reason) { $this->logRejection($reason); };

        return $this->client->getAsync($uri, $options)->then(null, $onRejected);
    }

    public function postAsync(string $uri, array $options = [], callable $onRejected = null) : PromiseInterface
    {
        $onRejected = $onRejected ?? function($reason) { $this->logRejection($reason); };

        return $this->client->postAsync($uri, $options)->then(null, $onRejected);
    }

    public function get(string $uri, array $options = []) : ResponseInterface
    {
        return $this->client->get($uri, $options);
    }

    public function put(string $uri, array $options = []) : ResponseInterface
    {
        return $this->client->put($uri, $options);
    }
    
    public function post(string $uri, array $options = []) : ResponseInterface
    {
        return $this->client->post($uri, $options);
    }

    private function logRejection($reason)
    {
        if ($reason instanceof Exception) {
            $message = $reason->getMessage()."\n".$reason->getTraceAsString();
        } else {
            $message = $reason;
        }
        $this->getLog()->warning($message);
    }
}