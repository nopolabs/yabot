<?php
use Nopolabs\Yabot\ErrorExceptionHandler;
use Nopolabs\Yabot\YabotContainer;

require __DIR__.'/vendor/autoload.php';

set_error_handler([ErrorExceptionHandler::class, 'handler']);

$config = require __DIR__.'/config.php';

$container = new YabotContainer();

$container->load(__DIR__.'/config/plugins.yml');

$yabot = $container->getYabot($config);

$yabot->run();
