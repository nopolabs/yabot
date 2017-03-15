<?php

namespace Nopolabs\Yabot\Bot;


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

    public function byId($id) : User
    {
        if (isset($this->usersById[$id])) {
            return $this->users[$this->usersById[$id]];
        } else {
            return null;
        }
    }

    public function byName($name) : User
    {
        if (isset($this->usersByName[$name])) {
            return $this->users[$this->usersByName[$name]];
        } else {
            return null;
        }
    }
}
