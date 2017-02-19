<?php

namespace Nopolabs\Yabot;


class ChannelPlugin extends BasePlugin
{
    protected function prepareMatchers(array $config) : array
    {
        $channel = $config['channel'];

        $matchers = [];
        foreach ($config['matchers'] as $name => $params) {
            $params = is_array($params) ? $params : ['pattern' => $params];
            $params['channel'] = $channel;
            $matchers[$name] = $params;
        }

        return $matchers;
    }
}