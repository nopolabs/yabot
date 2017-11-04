<?php

namespace Nopolabs\Yabot\Tests\Slack;


use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Slack\Users;
use PHPUnit\Framework\TestCase;
use Slack\User;

class UsersTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function testUsers()
    {
        $user = $this->newPartialMockWithExpectations(User::class, [
            'getId' => ['invoked' => $this->any(), 'result' => 'U00GROOT0'],
            'getUsername' => ['invoked' => $this->any(), 'result' => 'Groot'],
        ]);

        $users = new Users();

        $this->assertNull($users->byId('U00GROOT0'));
        $this->assertNull($users->byId('U00UNKN00'));
        $this->assertNull($users->byName('Groot'));
        $this->assertNull($users->byId('Unknown'));

        $users->update([$user]);

        $this->assertSame($user, $users->byId('U00GROOT0'));
        $this->assertNull($users->byId('U00UNKN00'));
        $this->assertSame($user, $users->byName('Groot'));
        $this->assertNull($users->byId('Unknown'));

        $this->assertEquals(['U00GROOT0' => 'Groot'], $users->getMap());
    }
}