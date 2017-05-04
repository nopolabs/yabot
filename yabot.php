<?php
use Nopolabs\Yabot\ErrorExceptionHandler;
use Nopolabs\Yabot\YabotContainer;

require __DIR__.'/vendor/autoload.php';

set_error_handler([ErrorExceptionHandler::class, 'handler']);

$container = new YabotContainer();
$container->load(__DIR__.'/config/plugins.yml');

$config = require __DIR__.'/config.php';
$container->overrideParameters($config);

$yabot = $container->getYabot();
$yabot->run();
