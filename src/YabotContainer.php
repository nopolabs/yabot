<?php

namespace Nopolabs\Yabot;

use Exception;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class YabotContainer extends ContainerBuilder
{
    const YABOT_ID = 'yabot';
    const YABOT_PLUGIN_TAG = 'yabot.plugin';

    public function __construct($servicesPath = __DIR__.'/../config/yabot.xml')
    {
        parent::__construct();
        $this->load($servicesPath);
    }

    public function load($file, $type = null)
    {
        $type = $type ?? pathinfo($file)['extension'];

        if ($type === 'xml') {
            $this->loadXmlFile($file);
            return;
        }

        if ($type === 'yml') {
            $this->loadYamlFile($file);
            return;
        }

        throw new Exception("Do not know how to load $file");
    }

    public function overrideParameters(array $parameters)
    {
        $this->getParameterBag()->add($parameters);
    }

    public function getYabot() : Yabot
    {
        /** @var Yabot $yabot */
        $yabot = $this->get(self::YABOT_ID);

        $plugins = $this->getTaggedPlugins(self::YABOT_PLUGIN_TAG);

        $this->initPlugins($plugins);

        $yabot->init($plugins);

        return $yabot;
    }

    public function getParameterOrDefault($name, array $default = []) : array
    {
        return $this->hasParameter($name) ? $this->getParameter($name) : $default;
    }

    private function getTaggedPlugins($tag) : array
    {
        return array_reduce(
            array_keys($this->findTaggedServiceIds($tag)),
            function(array $plugins, string $pluginId) {
                $plugins[$pluginId] = $this->get($pluginId);
                return $plugins;
            },
            []
        );
    }

    private function initPlugins(array $plugins)
    {
        array_map(
            function(string $pluginId, PluginInterface $plugin) {
                $config = $this->getParameterOrDefault($pluginId);
                $plugin->init($pluginId, $config);
            },
            array_keys($plugins), $plugins
        );
    }

    protected function loadXmlFile($file)
    {
        $loader = new XmlFileLoader($this, new FileLocator());
        $loader->load($file);
    }

    protected function loadYamlFile($file)
    {
        $loader = new YamlFileLoader($this, new FileLocator());
        $loader->load($file);
    }
}