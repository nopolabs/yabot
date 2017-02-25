<?php
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

require __DIR__.'/vendor/autoload.php';

$container = new ContainerBuilder();
$loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/config'));
$loader->load('services.xml');

$yabot = $container->get('yabot');

$yabot->run();
