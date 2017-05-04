#!/bin/bash -e

usage() {
    echo 'Usage: ' `basename "$0"` '[dir]'
}

confirm() {
    # call with a prompt string or use a default
    read -r -p "${1:-Are you sure? [y/N]} " response
    case "$response" in
        [yY][eE][sS]|[yY]) true ;;
        *)                 false ;;
    esac
}

require() {
    command -v "$1" >/dev/null 2>&1 || { echo >&2 "I require '$1' but it's not installed.  Aborting."; exit 1; }
}

check() {
    require php
    require composer

    PHP_MAJOR_VERSION="$(php -v | awk '/^PHP / { split($2,v,"."); print v[1]; exit; }')"
    if [ "$PHP_MAJOR_VERSION" -lt "7" ]; then
        echo -n "Requires php 7+, found: "
        php -v | grep '^PHP '
        confirm || exit 1
    fi
}

init() {
    check

    DIR=${1:-.}

    if [ ! -d "$DIR" ]; then
        mkdir "$DIR"
    fi

    if [ "$(ls -A ${DIR})" ]; then
         echo "Directory '$DIR' is not empty."
         confirm || exit 1
    fi

    set -x
    cd "${DIR}"
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
}

if [ "$1" = '--help' ]; then
    usage && exit 0
fi

init "$1"
