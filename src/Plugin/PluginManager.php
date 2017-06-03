<?php

namespace Nopolabs\Yabot\Plugin;


use Exception;
use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;
use Throwable;

class PluginManager
{
    use LogTrait;

    const NO_PREFIX = '<none>';
    const AUTHED_USER_PREFIX = 'AUTHED_USER';

    /** @var array */
    private $plugins;

    /** @var array */
    private $prefixMap;

    public function __construct(LoggerInterface $logger)
    {
        $this->setLog($logger);

        $this->plugins = [];
        $this->prefixMap = [];
    }

    public function loadPlugin($pluginId, PluginInterface $plugin)
    {
        if (isset($this->plugins[$pluginId])) {
            $this->warning("$pluginId already loaded, ignoring duplicate.");
            return;
        }

        $this->addPlugin($pluginId, $plugin);

        $this->info('loaded', ['pluginId' => $pluginId, 'prefix' => $plugin->getPrefix()]);
    }

    public function getHelp() : array
    {
        $help = [];
        foreach ($this->getPrefixMap() as $prefix => $plugins) {
            /** @var PluginInterface $plugin */
            foreach ($plugins as $pluginId => $plugin) {
                $help[] = $pluginId;
                foreach (explode("\n", $plugin->help()) as $line) {
                    $prefix = ($prefix === self::NO_PREFIX) ? '' : $prefix;
                    $help[] = '    '.str_replace('<prefix>', $prefix, $line);
                }
            }
        }

        return $help;
    }

    public function getStatuses() : array
    {
        $count = count($this->plugins);

        $statuses = [];
        $statuses[] = "There are $count plugins loaded.";
        foreach (array_values($this->getPrefixMap()) as $plugins) {
            /** @var PluginInterface $plugin */
            foreach ($plugins as $pluginId => $plugin) {
                $statuses[] = "$pluginId ".$plugin->status();
            }
        }

        return $statuses;
    }

    public function dispatchMessage(Message $message)
    {
        $text = $message->getFormattedText();

        $this->info('dispatchMessage: ', ['formattedText' => $text]);

        foreach ($this->getPrefixMap() as $prefix => $plugins) {

            if (!($matches = $this->matchesPrefix($prefix, $text))) {
                continue;
            }

            $this->debug('Matched prefix', ['prefix' => $prefix]);

            $text = ltrim($matches[1]);

            $message->setPluginText($text);

            foreach ($plugins as $pluginId => $plugin) {
                /** @var PluginInterface $plugin */
                try {
                    $plugin->handle($message);
                } catch (Throwable $throwable) {
                    $message = "Unhandled Exception in $pluginId\n"
                        .$throwable->getMessage()."\n"
                        .$throwable->getTraceAsString()."\n"
                        ."Payload data: ".json_encode($message->getData());
                    $this->warning($message);
                }

                if ($message->isHandled()) {
                    return;
                }
            }

            if ($message->isHandled()) {
                return;
            }
        }
    }

    public function updatePrefixes($authedUsername)
    {
        $updated = [];

        foreach ($this->getPrefixMap() as $prefix => $plugins) {
            if ($prefix === self::AUTHED_USER_PREFIX) {
                $prefix = '@'.$authedUsername;
            }

            $updated[$prefix] = $plugins;
        }

        $this->prefixMap = $updated;
    }

    public function matchesPrefix($prefix, $text) : array
    {
        if ($prefix === self::NO_PREFIX) {
            return [$text, $text];
        }

        preg_match("/^$prefix\\s+(.*)/", $text, $matches);

        return $matches;
    }

    protected function addPlugin($pluginId, PluginInterface $plugin)
    {
        $this->plugins[$pluginId] = $plugin;

        $prefix = $this->getPrefix($plugin);

        if (!isset($this->prefixMap[$prefix])) {
            $this->prefixMap[$prefix] = [];
        }

        $this->prefixMap[$prefix][$pluginId] = $plugin;
    }

    protected function getPrefix(PluginInterface $plugin) : string
    {
        $prefix = $plugin->getPrefix();

        if ($prefix === '') {
            return self::NO_PREFIX;
        }

        return $prefix;
    }

    protected function getPrefixMap() : array
    {
        return $this->prefixMap;
    }
}