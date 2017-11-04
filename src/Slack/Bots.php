<?php

namespace Nopolabs\Yabot\Slack;


class Bots extends AbstractIdNameMap
{
    protected function getId($thing): string
    {
        return $thing->getId();
    }

    protected function getName($thing): string
    {
        return $thing->getName();
    }
}
