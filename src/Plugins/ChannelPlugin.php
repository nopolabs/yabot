<?php

namespace Nopolabs\Yabot\Plugins;


class ChannelPlugin extends BasePlugin
{
    protected function prepareMatchers(array $options) : array
    {
        $channel = $options['channel'];

        $matchers = [];
        foreach ($options['matchers'] as $name => $params) {
            $params = is_array($params) ? $params : ['pattern' => $params];
            $params['channel'] = $channel;
            $matchers[$name] = $params;
        }

        return $matchers;
    }
}