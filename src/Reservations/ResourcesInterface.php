<?php

namespace Nopolabs\Yabot\Reservations;

use DateTime;
use GuzzleHttp\Promise\PromiseInterface;
use Slack\User;

interface ResourcesInterface
{
    public function isResource($key);

    public function getResource($key);

    public function setResource($key, $resource);

    public function getAll() : array;

    public function getKeys() : array;

    public function isReserved($key);

    public function reserve($key, User $user, DateTime $until = null);

    public function release($key);

    public function getStatus($key);

    public function getAllStatuses();

    public function getStatusAsync($key) : PromiseInterface;

    public function expireResources();
}