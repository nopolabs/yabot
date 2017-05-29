<?php

namespace Nopolabs\Yabot\Tests\Bot;


use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Bot\Users;
use PHPUnit\Framework\TestCase;
use Slack\User;

class UsersTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function testUsers()
    {
        $user = $this->newPartialMockWithExpectations(User::class, [
            ['getId', ['result' => 'U00GROOT0']],
            ['getUsername', ['result' => 'Groot']],
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
    }
}