<?php

namespace Slack;


use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Slack\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function testSayChannelNotFound()
    {
        /** @var Client $client */
        $client = $this->newPartialMockWithExpectations(Client::class, [
            ['getChannelByName', ['params' => ['unknown-channel'], 'result' => null]],
            ['getChannelById', ['params' => ['unknown-channel'], 'result' => null]],
            ['warning', ['params' => ['No channel, trying to say: what?']]],
        ]);

        $client->say('what?', 'unknown-channel');
    }
}