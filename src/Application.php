<?php
namespace Nopolabs\Yabot;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Noodlehaus\Config;
use Nopolabs\Yabot\Plugins\PluginInterface;
use Nopolabs\Yabot\Storage\FileStorage;
use Nopolabs\Yabot\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Slack\RealTimeClient;

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

        $config = $this->loadConfig($configPath);

        $storage = $this->getStorage($config);

        $logger = $this->getLogger($config);

        $loop = $this->getEventLoop();

        $guzzle = $this->getGuzzle();

        $client = $this->getRealTimeClient($config, $loop, $guzzle);

        $bot = new Yabot($config, $storage, $logger, $loop, $client, $guzzle);

        foreach ($config->get('plugins') as $className => $options) {
            $this->loadPlugin($className, $options, $bot);
        }

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

    protected function loadConfig($configPath) : Config
    {
        return new Config($configPath);
    }

    protected function getStorage(Config $config) : StorageInterface
    {
        return new FileStorage($config->get('storage'));
    }

    protected function getLogger(Config $config) : LoggerInterface
    {
        $logger = new Logger('bot');
        $logger->pushHandler(new StreamHandler($config->get('log'), Logger::DEBUG));

        return $logger;
    }

    protected function getEventLoop()
    {
        return Factory::create();
    }

    protected function getRealTimeClient(Config $config, LoopInterface $loop, ClientInterface $guzzle) : RealTimeClient
    {
        $client = new RealTimeClient($loop, $guzzle);
        $client->setToken($config->get('slack.token'));

        return $client;
    }

    protected function getGuzzle() : Client
    {
        return new Client();
    }

    protected function loadPlugin($className, array $config, Yabot $bot)
    {
        if (!class_exists($className)) {
            throw new \Exception('The plugin class "'.$className.'" could not be found.');
        }

        if (!in_array(PluginInterface::class, class_implements($className))) {
            throw new \Exception('The class "'.$className.'" idoes not implement "'.PluginInterface::class.'"');
        }

        /** @var PluginInterface $plugin */
        $plugin = new $className();
        $plugin->setMatchers($config);
        $plugin->setBot($bot);

        $bot->addPlugin($plugin);
    }
}
