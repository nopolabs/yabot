<?php

namespace Nopolabs\Yabot\Bot;


abstract class AbstractIdNameMap
{
    private $things = [];
    private $thingsById = [];
    private $thingsByName = [];

    abstract protected function getId($thing) : string;

    abstract protected function getName($thing) : string;

    public function update(array $things)
    {
        $this->things = $things;
        foreach ($things as $index => $thing) {
            $this->thingsById[$this->getId($thing)] = $index;
            $this->thingsByName[$this->getName($thing)] = $index;
        }
    }

    public function byId($id)
    {
        if (isset($this->thingsById[$id])) {
            return $this->things[$this->thingsById[$id]];
        } else {
            return null;
        }
    }

    public function byName($name)
    {
        if (isset($this->thingsByName[$name])) {
            return $this->things[$this->thingsByName[$name]];
        } else {
            return null;
        }
    }
}