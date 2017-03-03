<?php

namespace Nopolabs\Yabot;


use Exception;
use Nopolabs\Yabot\Bot\PluginInterface;
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

    public function __construct(ParameterBagInterface $parameterBag)
    {
        if (!$parameterBag->has(self::SLACK_TOKEN_KEY)) {
            throw new Exception('parameters must contain '.self::SLACK_TOKEN_KEY);
        }

        parent::__construct($parameterBag);

        if ($parameterBag->has('yabot.xml.path')) {
            $this->loadXml($parameterBag->get('yabot.xml.path'));
        } elseif ($parameterBag->has('yabot.yml.path')) {
            $this->loadYml($parameterBag->get('yabot.yml.path'));
        } else {
            $this->loadXml(__DIR__.'/../config/yabot.xml');
        }
    }

    public static function withConfig(array $parameters) : YabotContainer
    {
        $parameterBag = new ParameterBag($parameters);

        return new self($parameterBag);
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

    public function getYabot($pluginTag = self::YABOT_PLUGIN_TAG) : Yabot
    {
        $yabot = $this->get(self::YABOT_ID);
        $this->addTaggedPlugins($yabot, $pluginTag);
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
        $this->addPlugin($yabot, $this->get($pluginId));
    }

    public function addPlugin(Yabot $yabot, PluginInterface $plugin)
    {
        $yabot->addPlugin($plugin);
    }
}