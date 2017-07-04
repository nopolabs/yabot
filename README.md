# Yet Another Bot (yabot)

[![Build Status](https://travis-ci.org/nopolabs/yabot.svg?branch=master)](https://travis-ci.org/nopolabs/yabot)
[![Code Climate](https://codeclimate.com/github/nopolabs/yabot/badges/gpa.svg)](https://codeclimate.com/github/nopolabs/yabot)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nopolabs/yabot/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nopolabs/yabot/?branch=master)
[![License](https://poser.pugx.org/nopolabs/yabot/license)](https://packagist.org/packages/nopolabs/yabot)
[![Latest Stable Version](https://poser.pugx.org/nopolabs/yabot/v/stable)](https://packagist.org/packages/nopolabs/yabot)

yabot is a slack chat bot written in php.

## Quick start

You will need php 7.* and [composer](https://getcomposer.org/download/).

You can use
[yabot-init.sh](https://github.com/nopolabs/yabot/blob/master/bin/yabot-init.sh)
to initialize a yabot project.
You can download and run yabot-init.sh with this command
(take a [peek](https://github.com/nopolabs/yabot/blob/master/bin/yabot-init.sh) first):

    wget -O - https://raw.githubusercontent.com/nopolabs/yabot/master/bin/yabot-init.sh | bash

or:

    curl -L https://raw.githubusercontent.com/nopolabs/yabot/master/bin/yabot-init.sh | bash

or manually:

    composer init \
        --no-interaction \
        --stability dev \
        --repository '{"type":"vcs","url":"https://github.com/nopolabs/slack-client"}' \
        --repository '{"type":"vcs","url":"https://github.com/nopolabs/phpws.git"}'
    composer require nopolabs/yabot
    mkdir config
    cp -i vendor/nopolabs/yabot/yabot.php yabot.php
    cp -i vendor/nopolabs/yabot/config/plugins.example.yml config/plugins.yml
    cp -i vendor/nopolabs/yabot/config.example.php config.php
    cp -i vendor/nopolabs/yabot/.gitignore .gitignore

NOTE: yabot-init.sh uses nopolabs repositories for slack-client and phpws
because it depends on updates to coderstephen/slack-client
and devristo/phpws that are not yet available from those packages.
    
In order for yabot to listen to messages in your slack rooms
you need to provide a token which you get from Slack. 
Please read [Slack's documentation](https://get.slack.help/hc/en-us/articles/215770388)
for information on how to issue new authentication tokens.

Edit config.php and add your Slack API token:

    'slack.token' => 'SLACK-TOKEN-GOES-HERE',

*Do not save config.php in a public repository.*
The slack.token is *not* meant to be shared publicly.
yabot-init.sh creates a .gitignore file containing config.php,
if you are using a different VCS you will need to do something similar.

If you do accidentally commit the token to a public repo 
Slack will revoke it (as I discovered).

## Running yabot

    php yabot.php

## Configuration

Yabot uses a [Symfony dependency-injection](http://symfony.com/doc/current/components/dependency_injection.html)
container for configuration.

`yabot.php` loads three configuration files: 
`vendor/nopolabs/yabot/config/yabot.xml`, 
`config/plugins.yml`, and `config.php`.

`vendor/nopolabs/yabot/config/yabot.xml` defines core services used by
Yabot and available for plugins to get from the container. You should
not need to modify this file.

`config/plugins.yml` provides a place to configure plugins and shared
services for your Yabot application. See the discussion of 
[plugins](#plugins) below.

`config.php` provides a place to set or override runtime settings.

[Importing configuration files](http://symfony.com/doc/current/service_container/import.html)

## Logging

Logging is configured in config.php:

    'log.file' => 'logs/bot.log',
    'log.name' => 'bot',
    'log.level' => 'DEBUG',

Setting 'log.file' to 'php://stdout' can be useful during development to
direct logging information to the terminal where you have started yabot.

## Plugins <a name="plugins"></a>

See [yabot-plugins](https://github.com/nopolabs/yabot-plugins) for examples.

Yabot uses plugins to know what to listen for and how to respond.

There are examples in `src/Examples`, `src/Reservations`, and `src/Queue`.

Minimally a plugin must implement `Nopolabs\Yabot\Bot\PluginInterface`.

Plugins declared in `plugins.yml` with the tag `yabot.plugin` will be
loaded automatically: 

    services:
        plugin.help:
            class: Nopolabs\Yabot\Bot\HelpPlugin
            arguments:
                - '@logger'
                - '@yabot'
            tags:
                - { name: yabot.plugin }

### Theory of operation

Yabot uses Slack's [Real Time Messaging API](https://api.slack.com/rtm).

Message events are dispatched to each plugin in the order in which they were loaded.

Plugins have an opportunity to react to the messages they receive, e.g. by replying,
and may optionally mark a message as handled to interrupt further dispatching.

Plugin configuration is used to select which messages a plugin will react to 
and how it will react.

### Plugin config

Plugins built using `PluginTrait` provide a default configuration which may be overridden in `config.php`, e.g.:

    'plugin.help' => [
        'priority' => PluginManager::DEFAULT_PRIORITY, // optional, higher priority is dispatched sooner
        'prefix' => PluginManager::AUTHED_USER_PREFIX, // optional, string
        'isBot' => false,                              // optional, true, false, or null
        'channels' => [],                              // optional, array of strings
        'users' => [],                                 // optional, array of strings
        'matchers' => [
            'yabotHelp' => [
                'isBot' => null,                          // optional, true, false, or null
                'channels' => [],                         // optional, array of strings
                'users' => [],                            // optional, array of strings
                'pattern' => "/^help (?'topic'\\w+)\\b/", // pattern applied by preg_match()
                'method' => 'help',                       // method called to handle accepted messages
            ],
        ],
    ],

Matcher shorthand syntax:

    // shorthand:
    'help' => "/^help (?'topic'\\w+)\\b/",
    
    // expands to:
    'help' => [
        'isBot' => null,   // null matches bot or non-bot
        'channels => [],   // empty array matches any channel
        'users' => [],     // empty array matches any user
        'pattern => "/^help (?'topic'\\w+)\\b/",
        'method' => 'help',
    ],

If the pattern matches the method on the plugin object gets called
with the message and any fields captured by the matcher pattern.

## Responding to a Message

#### Replying in the same channel as the message

    // assuming: 'help' => "/^help (?'topic'\\w+)\\b/"
    public function help(MessageInterface $msg, array $matches)
    {
        $topic = $matches[1];
        $msg->reply("you want help with $topic");
    }

#### In a Thread

        $msg->thread("here are the details...");
        
#### In a specific channel

        $msg->say("lunchtime!", 'general');

## Users and Channels

Slack messages use ids to reference users and channels, e.g.:

| Displayed | Message text |
| ------------- | ------------- |
| Why not join #tech-chat? | Why not join <#C024BE7LR>? |
| Hey @alice, did you see my file? | Hey <@U024BE7LH>, did you see my file? |

`Slack\Client` manages `Slack\Users` and `Slack\Channels` objects and provides methods to help
map user and channel names to ids and ids to names.

## Message formatting and attachments

Yabot uses the Slack REST API to post messages because the web socket API
doesn't support formatting and attachments. See:
[Slack API Messages](https://api.slack.com/docs/messages)

