<?php
use Nopolabs\Yabot\YabotContainer;

require __DIR__.'/vendor/autoload.php';

$config = require __DIR__.'/config.php';

$container = new YabotContainer();

$container->loadYml(__DIR__.'/config/plugins.yml');

$yabot = $container->getYabot($config);

$yabot->run();
