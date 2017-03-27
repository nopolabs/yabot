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

    public function getYabot(array $config) : Yabot
    {
        $this->addConfig($config);

        /** @var Yabot $yabot */
        $yabot = $this->get(self::YABOT_ID);

        $this->addTaggedPlugins($yabot, self::YABOT_PLUGIN_TAG);

        return $yabot;
    }

    public function addTaggedPlugins(Yabot $yabot, $pluginTag = self::YABOT_PLUGIN_TAG)
    {
        $pluginIds = $this->findTaggedServiceIds($pluginTag);
        foreach ($pluginIds as $pluginId => $value) {
            $this->addPluginById($yabot, $pluginId);
        }
    }

    public function addPluginById(Yabot $yabot, $pluginId)
    {
        $logger = $this->get('logger');

        $logger->info("loading $pluginId");

        try {
            /** @var PluginInterface $plugin */
            $plugin = $this->get($pluginId);
            $this->addPlugin($yabot, $pluginId, $plugin);
        } catch (Exception $e) {
            $logger->warning("Unhandled Exception while loading $pluginId: ".$e->getMessage());
            $logger->warning($e->getTraceAsString());
        }
    }

    public function addPlugin(Yabot $yabot, $pluginId, PluginInterface $plugin)
    {
        $yabot->addPlugin($pluginId, $plugin);
    }
}