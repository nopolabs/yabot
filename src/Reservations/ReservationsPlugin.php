<?php

namespace Nopolabs\Yabot\Reservations;

use DateTime;
use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
use Psr\Log\LoggerInterface;

class ReservationsPlugin implements PluginInterface
{
    use PluginTrait;

    /** @var ResourcesInterface */
    protected $resources;

    public function __construct(
        MessageDispatcher $dispatcher,
        LoggerInterface $logger,
        ResourcesInterface $resources,
        array $config = [])
    {
        $this->setDispatcher($dispatcher);
        $this->setLog($logger);
        $this->resources = $resources;

        $default = [
            'resourceNamePlural' => 'resources',
            'channel' => 'general',
            'matchers' => [
                'reserveForever' => "/^reserve #resourceCapture# forever\\b/",
                'reserveUntil' => "/^reserve #resourceCapture# until (?'until'.+)/",
                'reserve' => "/^reserve #resourceCapture#/",

                'release' => "/^release #resourceCapture#/",
                'releaseMine' => "/^release mine\\b/",
                'releaseAll' => "/^release all\\b/",

                'list' => "/^list #resourceNamePlural#\\b/",
                'listMine' => "/^what #resourceNamePlural# are mine\\b/",
                'listFree' => "/^what #resourceNamePlural# are free\\b/",

                'isFree' => "/^is #resourceCapture# free\\b/",
            ],
        ];

        $config = array_merge($default, $config);

        $channel = $config['channel'];
        $matchers = $config['matchers'];
        $resourceNamePlural = $config['resourceNamePlural'];
        $resourceCapture = "(?'resource'".join('|', $resources->getKeys()).")";

        $matchers = $this->addToMatchers('channel', $channel, $matchers);
        if (isset($config['commandPrefix'])) {
            $prefix = $config['commandPrefix'];
            $matchers = $this->replaceInPatterns('/^', "/^$prefix ", $matchers);
        }
        $matchers = $this->replaceInPatterns('#resourceNamePlural#', $resourceNamePlural, $matchers);
        $matchers = $this->replaceInPatterns('#resourceCapture#', $resourceCapture, $matchers);
        $matchers = $this->replaceInPatterns(' ', "\\s+", $matchers);

        $this->setMatchers($matchers);
    }

    public function reserve(MessageInterface $msg, array $matches)
    {
        $key = $matches['resource'];
        $results = $this->placeReservation($msg, $key, new DateTime('+ 12 hours'));
        $msg->reply(join("\n", $results));
        $msg->setHandled(true);
    }

    public function reserveForever(MessageInterface $msg, array $matches)
    {
        $key = $matches['resource'];
        $results = $this->placeReservation($msg, $key);
        $msg->reply(join("\n", $results));
        $msg->setHandled(true);
    }

    public function reserveUntil(MessageInterface $msg, array $matches)
    {
        $key = $matches['resource'];
        $until = $matches['until'];
        $results = $this->placeReservation($msg, $key, new DateTime($until));
        $msg->reply(join("\n", $results));
        $msg->setHandled(true);
    }

    public function release(MessageInterface $msg, array $matches)
    {
        $key = $matches['resource'];
        $results = $this->releaseReservation($msg, $key);
        $msg->reply(join("\n", $results));
        $msg->setHandled(true);
    }

    public function releaseMine(MessageInterface $msg, array $matches)
    {
        $results = [];
        foreach ($this->resources->getAll() as $key => $resource) {
            if ($resource['user'] === $msg->getUsername()) {
                $results = array_merge($results, $this->releaseReservation($msg, $key));
            }
        }
        $msg->reply(join("\n", $results));
        $msg->setHandled(true);
    }

    public function releaseAll(MessageInterface $msg, array $matches)
    {
        $results = [];
        foreach ($this->resources->getKeys() as $key) {
            $results = array_merge($results, $this->releaseReservation($msg, $key));
        }
        $msg->reply(join("\n", $results));
        $msg->setHandled(true);
    }

    public function list(MessageInterface $msg, array $matches)
    {
        $results = $this->resources->getAllStatuses();
        $msg->reply(join("\n", $results));
        $msg->setHandled(true);
    }

    public function listMine(MessageInterface $msg, array $matches)
    {
        $results = [];
        foreach ($this->resources->getAll() as $key => $resource) {
            if ($resource['user'] === $msg->getUsername()) {
                $results[] = $key;
            }
        }
        $msg->reply(join(',', $results));
        $msg->setHandled(true);
    }

    public function listFree(MessageInterface $msg, array $matches)
    {
        $results = [];
        foreach ($this->resources->getAll() as $key => $resource) {
            if (empty($resource)) {
                $results[] = $key;
            }
        }
        $msg->reply(join(',', $results));
        $msg->setHandled(true);
    }

    public function isFree(MessageInterface $msg, array $matches)
    {
        $results = [];
        $key = $matches['resource'];
        $resource = $this->resources->getResource($key);
        if ($resource === null) {
            $results[] = "$key not found.";
        } else {
            if (empty($resource)) {
                $results[] = "$key is free.";
            } else {
                $results[] = "$key is reserved by {$resource['user']}";
            }
        }
        $msg->reply(join(',', $results));
        $msg->setHandled(true);
    }

    protected function placeReservation(MessageInterface $msg, $key, DateTime $until = null) : array
    {
        $results = [];
        $resource = $this->resources->getResource($key);

        if ($resource === null) {
            $results[] = "$key not found.";
        } else {
            if (empty($resource)) {
                $this->resources->reserve($key, $msg->getUser(), $until);
                $results[] = "Reserved $key for {$msg->getUser()->getUsername()}.";
            } elseif ($resource['user'] === $msg->getUsername()) {
                $this->resources->reserve($key, $msg->getUser(), $until);
                $results[] = "Updated $key for {$msg->getUser()->getUsername()}.";
            } else {
                $results[] = "$key is reserved by {$resource['user']}";
            }
            $results[] = $this->resources->getStatus($key);
        }

        return $results;
    }

    protected function releaseReservation(MessageInterface $msg, $key) : array
    {
        $results = [];
        $resource = $this->resources->getResource($key);

        if ($resource === null) {
            $results[] = "$key not found.";
        } else {
            if (empty($resource)) {
                $results[] = "$key is not reserved.";
            } else {
                $this->resources->release($key);
                $results[] = "Released $key.";
            }
        }

        return $results;
    }
}