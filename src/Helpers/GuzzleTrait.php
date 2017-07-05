<?php

namespace Nopolabs\Yabot\Helpers;


use GuzzleHttp\Promise\PromiseInterface;
use Nopolabs\Yabot\Guzzle\Guzzle;
use Psr\Http\Message\ResponseInterface;

trait GuzzleTrait
{
    /** @var Guzzle */
    private $guzzle;

    /** @var array */
    private $options = [];

    public function setGuzzle(Guzzle $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    public function getGuzzle() : Guzzle
    {
        return $this->guzzle;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function getOptions() : array
    {
        return $this->options;
    }

    public function getAsync(string $uri, array $options = []) : PromiseInterface
    {
        $options = $this->addOptions($options);

        return $this->getGuzzle()->getAsync($uri, $options);
    }

    public function postAsync(string $uri, array $options = []) : PromiseInterface
    {
        $options = $this->addOptions($options);

        return $this->getGuzzle()->postAsync($uri, $options);
    }

    public function post(string $uri, array $options = []) : ResponseInterface
    {
        $options = $this->addOptions($options);

        return $this->getGuzzle()->post($uri, $options);
    }

    private function addOptions(array $options) : array
    {
        return array_merge($options, $this->getOptions());
    }
}