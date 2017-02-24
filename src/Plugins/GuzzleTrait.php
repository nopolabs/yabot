<?php

namespace Nopolabs\Yabot\Plugins;


use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;

trait GuzzleTrait
{
    /** @var Client */
    private $guzzle;

    public function setGuzzle(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    public function getGuzzle() : Client
    {
        return $this->guzzle;
    }

    public function getAsync($uri) : PromiseInterface
    {
        return $this->guzzle->getAsync($uri);
    }
}