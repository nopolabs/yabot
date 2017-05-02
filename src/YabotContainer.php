<?php

namespace Nopolabs\Yabot;

use Exception;
use Nopolabs\Yabot\Bot\PluginInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class YabotContainer extends ContainerBuilder
{
    const YABOT_ID = 'yabot';
    const YABOT_PLUGIN_TAG = 'yabot.plugin';
    const SLACK_TOKEN_KEY = 'slack.token';

    public function __construct($servicesPath = __DIR__.'/../config/yabot.xml')
    {
        parent::__construct();
        $this->load($servicesPath);
    }

    public function addConfig(array $parameters)
    {
        $this->getParameterBag()->add($parameters);
    }

    public function load($file)
    {
        $extension = pathinfo($file)['extension'];
        if ($extension === 'xml') {
            $this->loadXml($file);
        } elseif ($extension === 'yml') {
            $this->loadYml($file);
        } else {
            throw new Exception("Do not know how to load $file");
        }
    }

    public function loadXml($file)
    {
        $loader = new XmlFileLoader($this, new FileLocator());
        $loader->load($file);
    }

    public function loadYml($file)
    {
        $loader = new YamlFileLoader($this, new FileLocator());
        $loader->load($file);
    }

    public function getYabot() : Yabot
    {
        /** @var Yabot $yabot */
        $yabot = $this->get(self::YABOT_ID);

        $plugins = $this->getTaggedPlugins(self::YABOT_PLUGIN_TAG);

        $yabot->init($plugins);

        return $yabot;
    }

    protected function getTaggedPlugins($tag) : array
    {
        $plugins = [];

        $pluginIds = $this->findTaggedServiceIds($tag);
        foreach ($pluginIds as $pluginId => $value) {
            /** @var PluginInterface $plugin */
            $plugin = $this->get($pluginId);
            $config = $this->getParameterOrDefault($pluginId);
            $plugin->init($pluginId, $config);
            $plugins[$pluginId] = $plugin;
        }

        return $plugins;
    }

    protected function getParameterOrDefault($name, array $default = []) : array
    {
        return $this->hasParameter($name)? $this->getParameter($name) : $default;
    }
}