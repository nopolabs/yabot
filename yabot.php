<?php
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Yabot;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

require __DIR__.'/vendor/autoload.php';

$container = new ContainerBuilder();
$loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/config'));
$loader->load('services.xml');

$plugins = new YamlFileLoader($container, new FileLocator(__DIR__.'/config'));
$plugins->load('plugins.yml');

/** @var Yabot $yabot */
$yabot = $container->get('yabot');

$pluginIds = $container->findTaggedServiceIds('yabot.plugin');
foreach ($pluginIds as $pluginId => $value) {
    /** @var PluginInterface $plugin */
    $plugin = $container->get($pluginId);
    $yabot->addPlugin($plugin);
}

$yabot->run();
