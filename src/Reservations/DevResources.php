<?php

namespace Nopolabs\Yabot\Reservations;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Nopolabs\Yabot\Bot\SlackClient;
use Nopolabs\Yabot\Helpers\GuzzleTrait;
use Nopolabs\Yabot\Storage\StorageInterface;
use React\EventLoop\LoopInterface;

class DevResources extends Resources
{
    use GuzzleTrait;

    protected $refTagUrlTemplate;

    public function __construct(
        SlackClient $slack,
        StorageInterface $storage,
        LoopInterface $eventLoop,
        Client $guzzle,
        array $config)
    {
        parent::__construct($slack, $storage, $eventLoop, $config);
        $this->setGuzzle($guzzle);
        $this->refTagUrlTemplate = $config['refTagUrlTemplate'];
    }

    protected function getStatusAsync($key) : PromiseInterface
    {
        return $this->getRefTag($key)->then(function($refTag) use ($key) {
            if ($resource = $this->getResource($key)) {
                $userId = $resource['userId'];
                $until = $resource['until'];
                return "`$key` has `$refTag` and is reserved by <@$userId> until `$until`";
            } else {
                return "`$key` has `$refTag` and is FREE";
            }
        });
    }

    private function getRefTag($key) : PromiseInterface
    {
        $uri = str_replace('#KEY#', $key, $this->refTagUrlTemplate);

        return $this->getAsync($uri)->then(
            function(Response $response) {
                $body = $response->getBody();
                return trim($body);
            },
            function(RequestException $e) {
                $class = preg_replace('/^.*\\\/', '', get_class($e));
                return "$class code={$e->getCode()}";
            }
        );
    }
}