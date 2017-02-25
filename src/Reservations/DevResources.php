<?php

namespace Nopolabs\Yabot\Reservations;


use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Nopolabs\Yabot\Plugins\GuzzleTrait;
use Nopolabs\Yabot\Yabot;

class DevResources extends Resources
{
    use GuzzleTrait;

    protected $refTagUrlTemplate;

    public function __construct(Yabot $bot, array $config)
    {
        parent::__construct($bot, $config);
        $this->setGuzzle($bot->getGuzzle());
        $this->refTagUrlTemplate = $config['refTagUrlTemplate'];
    }

    public function getStatusAsync($key) : PromiseInterface
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

    public function getRefTag($key) : PromiseInterface
    {
        $uri = str_replace('#KEY#', $key, $this->refTagUrlTemplate);

        return $this->getAsync($uri)->then(
            function(Response $response) {
                return trim($response->getBody());
            },
            function(RequestException $e) {
                return $e->getMessage();
            }
        );
    }
}