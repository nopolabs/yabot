<?php
namespace Nopolabs\Yabot;

/**
 * Class Application
 */
class Application
{
    public function run()
    {
        $options = getopt('h', ['help', 'config::']);

        if (isset($options['h']) || isset($options['help'])) {
            $this->showHelp();
            exit(0);
        }

        $configPath = isset($options['config']) ? $options['config'] : 'config.php';

        $bot = new Bot($configPath);
        $bot->run();
    }

    public function showHelp()
    {
        echo <<< EOD
Yabot - Yet Another (slack chat) bot

Usage:
  yabot [options]

Options:
  -h, --help        Shows this help message
  --config file     Run using specified config file (default is config.php)

EOD;
    }
}
