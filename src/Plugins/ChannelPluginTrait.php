<?php

namespace Nopolabs\Yabot\Plugins;


trait ChannelPluginTrait
{
    use PluginTrait;

    public function prepare()
    {
        $channel = $this->config['channel'];
        $matchers = $this->config['matchers'];

        $this->matchers = $this->addChannelToMatchers($channel, $matchers);
    }

    public function addChannelToMatchers($channel, array $matchers)
    {
        $channelMatchers = [];

        foreach ($matchers as $name => $params) {
            $params = is_array($params) ? $params : ['pattern' => $params];
            $params['channel'] = $channel;
            $channelMatchers[$name] = $params;
        }

        return $channelMatchers;
    }
}