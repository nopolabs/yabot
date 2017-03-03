<?php
use Nopolabs\Yabot\YabotContainer;

require __DIR__.'/vendor/autoload.php';

$config = require __DIR__.'/config.php';

$container = YabotContainer::withConfig($config);

$container->loadYml(__DIR__.'/config/plugins.yml');

$yabot = $container->getYabot();

$yabot->run();
