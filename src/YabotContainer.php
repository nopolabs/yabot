<?php

namespace Nopolabs\Yabot;


use Exception;
use Nopolabs\Yabot\Bot\PluginInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class YabotContainer extends ContainerBuilder
{
    const YABOT_ID = 'yabot';
    const YABOT_PLUGIN_TAG = 'yabot.plugin';
    const SLACK_TOKEN_KEY = 'slack.token';

    public function __construct($servicesConfigPath = __DIR__.'/../config/yabot.xml')
    {
        parent::__construct();

        $extension = pathinfo($servicesConfigPath)['extension'];
        if ($extension === 'xml') {
            $this->loadXml($servicesConfigPath);
        } elseif ($extension === 'yml') {
            $this->loadYml($servicesConfigPath);
        } else {
            throw new Exception("Do not know how to load $servicesConfigPath");
        }
    }

    public function addConfig(array $parameters)
    {
        $this->getParameterBag()->add($parameters);
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

        $yabot = $this->get(self::YABOT_ID);

        $this->addTaggedPlugins($yabot, self::YABOT_PLUGIN_TAG);

        return $yabot;
    }

    public function addTaggedPlugins(Yabot $yabot, $pluginTag = self::YABOT_PLUGIN_TAG)
    {
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        $pluginIds = $this->findTaggedServiceIds($pluginTag);
        foreach ($pluginIds as $pluginId => $value) {
            $logger->info("loading $pluginId");
            $this->addPluginById($yabot, $pluginId);
        }
    }

    public function addPluginById(Yabot $yabot, $pluginId)
    {
        $this->addPlugin($yabot, $this->get($pluginId));
    }

    public function addPlugin(Yabot $yabot, PluginInterface $plugin)
    {
        $yabot->addPlugin($plugin);
    }
}