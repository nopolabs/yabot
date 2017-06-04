<?php
namespace Nopolabs\Yabot\Tests\Plugin;

use Nopolabs\Yabot\Plugin\PluginManager;

class TestPluginManager extends PluginManager
{
    public function getPluginMap() : array
    {
        return $this->pluginMap;
    }

    public function getPriorityMap() : array
    {
        return $this->priorityMap;
    }
}