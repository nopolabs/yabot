<?php

namespace Nopolabs\Yabot\Plugin;


use Exception;
use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Yabot;
use Psr\Log\LoggerInterface;
use Slack\User;
use Throwable;

class PluginManager
{
    use LogTrait;

    const NO_PREFIX = '<none>';
    const AUTHED_USER_PREFIX = '<authed_user>';
    const DEFAULT_PRIORITY = 100;

    /** @var User */
    protected $authedUser;

    /** @var array */
    protected $pluginMap;

    /** @var array */
    protected $priorityMap;

    public function __construct(LoggerInterface $logger)
    {
        $this->setLog($logger);

        $this->priorityMap = [];
    }

    public function loadPlugin($pluginId, PluginInterface $plugin)
    {
        if (isset($this->pluginMap[$pluginId])) {
            $this->warning("$pluginId already loaded, ignoring duplicate.");
            return;
        }

        $this->addPlugin($pluginId, $plugin);

        $this->info('loaded', ['pluginId' => $pluginId, 'prefix' => $plugin->getPrefix()]);
    }

    public function getHelp() : array
    {
        $help = [];
        /** @var PluginInterface $plugin */
        foreach ($this->getPluginMap() as $pluginId => $plugin) {
            $prefix = $this->helpPrefix($plugin->getPrefix());
            $help[] = $pluginId;
            $lines = explode("\n", trim($plugin->help()));
            foreach ($lines as $line) {
                $help[] = '    '.str_replace('<prefix> ', $prefix, $line);
            }
        }

        return $help;
    }

    public function getStatuses() : array
    {
        $count = count($this->getPluginMap());

        $statuses = [];
        $statuses[] = "There are $count plugins loaded.";
        foreach ($this->getPluginMap() as $pluginId => $plugin) {
            /** @var PluginInterface $plugin */
            $statuses[] = "$pluginId ".$plugin->status();
        }

        return $statuses;
    }

    public function setAuthedUser(User $authedUser)
    {
        $this->authedUser = $authedUser;

        $this->updatePrefixes($authedUser->getUsername());
    }

    public function matchesPrefix($prefix, $text) : array
    {
        if ($prefix === self::NO_PREFIX) {
            return [$text, $text];
        }

        preg_match("/^$prefix\\s+(.*)/", $text, $matches);

        return $matches;
    }

    public function dispatchMessage(Message $message)
    {
        $text = $message->getFormattedText();

        $this->info('dispatchMessage: ', [
            'formattedText' => $text,
            'user' => $message->getUsername(),
            'channel' => $message->getChannel()->getName(),
            'isBot' => $message->isBot(),
            'isSelf' => $message->isSelf(),
        ]);

        foreach ($this->getPriorityMap() as $priority => $prefixMap) {
            $this->debug("Dispatching priority $priority");

            $this->dispatchToPrefixes($prefixMap, $message, $text);

            if ($message->isHandled()) {
                return;
            }
        }
    }

    protected function dispatchToPrefixes(array $prefixMap, Message $message, string $text)
    {
        foreach ($prefixMap as $prefix => $plugins) {
            if ($matches = $this->matchesPrefix($prefix, $text)) {
                $this->debug("Dispatching prefix '$prefix'");

                $message->setPluginText(ltrim($matches[1]));

                $this->dispatchToPlugins($plugins, $message);

                if ($message->isHandled()) {
                    return;
                }
            }
        }
    }

    protected function dispatchToPlugins(array $plugins, Message $message)
    {
        foreach ($plugins as $pluginId => $plugin) {
            /** @var PluginInterface $plugin */
            try {
                $plugin->handle($message);

            } catch (Throwable $throwable) {
                $errmsg = "Unhandled Exception in $pluginId\n"
                    .$throwable->getMessage()."\n"
                    .$throwable->getTraceAsString()."\n"
                    ."Payload data: ".json_encode($message->getData());
                $this->warning($errmsg);
            }

            if ($message->isHandled()) {
                return;
            }
        }
    }

    protected function addPlugin($pluginId, PluginInterface $plugin)
    {
        $this->pluginMap[$pluginId] = $plugin;

        $priority = $plugin->getPriority();
        if (!isset($this->priorityMap[$priority])) {
            $this->priorityMap[$priority] = [];
        }

        $prefix = $plugin->getPrefix();
        if (!isset($this->priorityMap[$priority][$prefix])) {
            $this->priorityMap[$priority][$prefix] = [];
        }

        $this->priorityMap[$priority][$prefix][$pluginId] = $plugin;

        krsort($this->priorityMap);
    }

    protected function getPluginMap() : array
    {
        return $this->pluginMap;
    }

    protected function getPriorityMap() : array
    {
        return $this->priorityMap;
    }

    protected function setPriorityMap(array $priorityMap)
    {
        $this->priorityMap = $priorityMap;
    }

    protected function getAuthedUser()
    {
        return $this->authedUser;
    }

    protected function updatePrefixes($authedUsername)
    {
        $updatedPriorityMap = [];
        foreach ($this->getPriorityMap() as $priority => $prefixMap) {
            $updatedPrefixMap = [];
            foreach ($prefixMap as $prefix => $plugins) {
                if ($prefix === self::AUTHED_USER_PREFIX) {
                    $prefix = '@'.$authedUsername;
                }
                $updatedPrefixMap[$prefix] = $plugins;
            }
            $updatedPriorityMap[$priority] = $updatedPrefixMap;
        }
        $this->priorityMap = $updatedPriorityMap;
    }

    protected function helpPrefix($prefix)
    {
        if ($prefix === self::NO_PREFIX) {
            return '';
        }

        if ($prefix === self::AUTHED_USER_PREFIX) {
            return '@'.$this->getAuthedUser()->getUsername().' ';
        }

        return "$prefix ";
    }
}