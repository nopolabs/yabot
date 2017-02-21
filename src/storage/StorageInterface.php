<?php

interface StorageInterface
{
    public function save($key, $value);

    public function get($key);

    public function delete($key);

    public function all();
}