<?php

namespace Nopolabs\Yabot\Helpers;


use GuzzleHttp\Promise\PromiseInterface;
use Nopolabs\Yabot\Guzzle\Guzzle;
use Psr\Http\Message\ResponseInterface;

trait GuzzleTrait
{
    /** @var Guzzle */
    private $guzzle;

    public function setGuzzle(Guzzle $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    public function getGuzzle() : Guzzle
    {
        return $this->guzzle;
    }

    public function getAsync($uri) : PromiseInterface
    {
        return $this->getGuzzle()->getAsync($uri);
    }

    public function post($uri, $options = []) : ResponseInterface
    {
        return $this->getGuzzle()->post($uri, $options);
    }
}