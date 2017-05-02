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

    /**
     * @param $id
     * @return User|null
     */
    public function byId($id)
    {
        if (isset($this->usersById[$id])) {
            return $this->users[$this->usersById[$id]];
        } else {
            return null;
        }
    }

    /**
     * @param $name
     * @return User|null
     */
    public function byName($name)
    {
        if (isset($this->usersByName[$name])) {
            return $this->users[$this->usersByName[$name]];
        } else {
            return null;
        }
    }
}
