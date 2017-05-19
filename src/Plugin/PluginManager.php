<?php

namespace Nopolabs\Yabot\Plugin;


use Exception;
use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;

class PluginManager
{
    use LogTrait;

    const NO_PREFIX = '<none>';

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

    public function getPrefixMap() : array
    {
        return $this->prefixMap;
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
                    if ($prefix === self::NO_PREFIX) {
                        $line = str_replace('<prefix> ', '', $line);
                    } else {
                        $line = str_replace('<prefix>', $prefix, $line);
                    }
                    $help[] = '    ' . $line;
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
        foreach ($this->getPrefixMap() as $prefix => $plugins) {
            /** @var PluginInterface $plugin */
            foreach ($plugins as $pluginId => $plugin) {
                $statuses[] = "$pluginId " . $plugin->status();
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
                } catch (Exception $e) {
                    $this->warning("Unhandled Exception in $pluginId: ".$e->getMessage());
                    $this->warning($e->getTraceAsString());
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
            if ($prefix === Yabot::AUTHED_USER) {
                $prefix = '@' . $authedUsername;
            } elseif (!$prefix) {
                $prefix = self::NO_PREFIX;
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

        $prefix = $plugin->getPrefix();

        if (!isset($this->prefixMap[$prefix])) {
            $this->prefixMap[$prefix] = [];
        }

        $this->prefixMap[$prefix][$pluginId] = $plugin;
    }
}