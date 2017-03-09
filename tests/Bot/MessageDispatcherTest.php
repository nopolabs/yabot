<?php
namespace Nopolabs\Yabot\Tests\Bot;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Bot\Message;
use Nopolabs\Yabot\Bot\MessageDispatcher;
use PHPUnit\Framework\TestCase;

class MessageDispatcherTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function testDispatchMessageAlreadyHandled()
    {
        $plugin = $this->createMock(\stdClass::class);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            'isHandled' => ['result' => true],
        ]);

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->newPartialMockWithExpectations(MessageDispatcher::class, [
            ['dispatchMessage', 'never']
        ]);

        $dispatcher->dispatch($plugin, $message, []);
    }

    public function testDispatchPrefixNotMatched()
    {
        $plugin = $this->createMock(\stdClass::class);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            'isHandled' => ['result' => false],
            'matchesPrefix' => ['result' => false],
        ]);

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->newPartialMockWithExpectations(MessageDispatcher::class, [
            ['dispatchMessage', 'never']
        ]);

        $dispatcher->dispatch($plugin, $message, []);
    }

    public function testDispatchMatcherDoesNotMatch()
    {
        $plugin = $this->createMock(\stdClass::class);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            'isHandled' => ['result' => false],
            'matchesPrefix' => ['result' => true],
        ]);

        $matchers = [
            'match1' => ['matcher-params']
        ];

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->newPartialMockWithExpectations(MessageDispatcher::class, [
            ['matchMessage', [
                'params' => [$message, 'match1', ['matcher-params']],
                'result' => false,
            ]]
        ]);

        $dispatcher->dispatch($plugin, $message, $matchers);
    }

    public function testDispatchMatcherMatches()
    {
        $plugin = $this->createMock(\stdClass::class);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            'isHandled' => ['result' => false, 'invoked' => 2],
            'matchesPrefix' => ['result' => true],
        ]);

        $matchers = [
            'match1' => ['method' => 'something'],
        ];

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->newPartialMockWithExpectations(MessageDispatcher::class, [
            ['matchMessage', [
                'params' => [$message, 'match1', ['method' => 'something']],
                'result' => ['something' => ['matches']],
            ]],
            ['dispatchMessage', [
                'params' => [$plugin, $message, ['something' => ['matches']]],
            ]],
        ]);

        $dispatcher->dispatch($plugin, $message, $matchers);
    }

    public function testDispatchMatcherMatchesTwice()
    {
        $plugin = $this->createMock(\stdClass::class);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            'isHandled' => ['result' => false, 'invoked' => 3],
            'matchesPrefix' => ['result' => true],
        ]);

        $matchers = [
            'match1' => ['method' => 'something'],
            'match2' => ['method' => 'something-else'],
        ];

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->newPartialMockWithExpectations(MessageDispatcher::class, [
            ['matchMessage', [
                'params' => [$message, 'match1', ['method' => 'something']],
                'result' => ['something' => ['matches']],
            ]],
            ['dispatchMessage', [
                'params' => [$plugin, $message, ['something' => ['matches']]],
            ]],
            ['matchMessage', [
                'params' => [$message, 'match2', ['method' => 'something-else']],
                'result' => ['something-else' => ['matches2']],
            ]],
            ['dispatchMessage', [
                'params' => [$plugin, $message, ['something-else' => ['matches2']]],
            ]],
        ]);

        $dispatcher->dispatch($plugin, $message, $matchers);
    }

    public function testDispatchMatcherMatchesSecondMatcher()
    {
        $plugin = $this->createMock(\stdClass::class);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            'isHandled' => ['result' => false, 'invoked' => 2],
            'matchesPrefix' => ['result' => true],
        ]);

        $matchers = [
            'match1' => ['method' => 'something'],
            'match2' => ['method' => 'something-else'],
        ];

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->newPartialMockWithExpectations(MessageDispatcher::class, [
            ['matchMessage', [
                'params' => [$message, 'match1', ['method' => 'something']],
                'result' => false,
            ]],
            ['matchMessage', [
                'params' => [$message, 'match2', ['method' => 'something-else']],
                'result' => ['something-else' => ['matches2']],
            ]],
            ['dispatchMessage', [
                'params' => [$plugin, $message, ['something-else' => ['matches2']]],
            ]],
        ]);

        $dispatcher->dispatch($plugin, $message, $matchers);
    }

    public function testDispatchMatcherHandledByFirstMatcher()
    {
        $plugin = $this->createMock(\stdClass::class);

        $message = $this->newPartialMockWithExpectations(Message::class, [
            ['isHandled', ['result' => false]],
            ['matchesPrefix', ['result' => true]],
            ['isHandled', ['result' => true]],
        ]);

        $matchers = [
            'match1' => ['method' => 'something'],
            'match2' => ['method' => 'something-else'],
        ];

        /** @var MessageDispatcher $dispatcher */
        $dispatcher = $this->newPartialMockWithExpectations(MessageDispatcher::class, [
            ['matchMessage', [
                'params' => [$message, 'match1', ['method' => 'something']],
                'result' => ['something' => ['matches']],
            ]],
            ['dispatchMessage', [
                'params' => [$plugin, $message, ['something' => ['matches']]],
            ]],
        ]);

        $dispatcher->dispatch($plugin, $message, $matchers);
    }
}