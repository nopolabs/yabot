<?php

namespace Nopolabs\Yabot;


use Slack\User;

class Users
{
    private $users = [];
    private $usersById = [];
    private $usersByName = [];

    /**
     * @param User[] $users
     */
    public function update(array $users)
    {
        $this->users = $users;
        foreach ($users as $index => $user) {
            $this->usersById[$user->getId()] = $index;
            $this->usersByName[$user->getUsername()] = $index;
        }
    }

    public function byId($id)
    {
        $index = $this->usersById[$id];
        return $this->users[$index];
    }

    public function byName($name)
    {
        $index = $this->usersByName[$name];
        return $this->users[$index];
    }
}