<?php
return [
    'slack.token' => 'SLACK-TOKEN-GOES-HERE',
    'storage.dir' => 'storage',
    'log.file' => 'logs/bot.log',
    'log.name' => 'bot',
    'log.level' => 'DEBUG',
    'guzzle.config' => [
        'timeout' => 5,
    ],
    'plugin.lookup.config' => [
        'channel' => 'random',
    ],
    'reservations.resources.config' => [
        'channel' => 'general',
        'keys' => ['dev1', 'dev2', 'dev3'],
    ],
];
