<?php
use Nopolabs\Yabot\ErrorExceptionHandler;
use Nopolabs\Yabot\YabotContainer;

require __DIR__.'/vendor/autoload.php';

set_error_handler([ErrorExceptionHandler::class, 'handler']);

$options = getopt("f:h");
if (isset($options['h'])) {
    exit('Usage: php yabot.php [-h] [-f configFile]'.PHP_EOL);
}

$configFile = $options['f'] ?? __DIR__ . '/config.php';
$config = require $configFile;
echo "Using $configFile\n";

$container = new YabotContainer();
$container->load(__DIR__.'/config/plugins.yml');
$container->overrideParameters($config);

$yabot = $container->getYabot();
$yabot->run();