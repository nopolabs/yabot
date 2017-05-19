<?php

namespace Nopolabs\Yabot\Slack;


class Channels extends AbstractIdNameMap
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
