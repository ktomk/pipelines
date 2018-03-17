#!/bin/bash
#
# run phpunit tests in watch w/ colors and desktop notification on failure
#
# usage: ./lib/testwatch.sh
#
set -euo pipefail
IFS=$'\n\t'

echo 0 >/dev/shm/testwatch

unit()
{
    local capture
    local result

    capture="$(php -dphar.readonly=0 -f vendor/bin/phpunit -- --color=always)"
    result=$?

    local testwatch=$(</dev/shm/testwatch)

    if [ $result -eq 0 ]; then
        if [ ${testwatch} -ne 0 ]; then
            notify-send "Tests Green Again" "$(date)" \
                -i /usr/share/icons/gnome/scalable/status/rotation-allowed-symbolic.svg
        fi
        echo 0 >/dev/shm/testwatch
    else
        if [ $((${testwatch} % 5)) -eq 0 ]; then
            # DISPLAY=:0.0 /usr/bin/notify-send - some use might require DISPLAY
            #                                     variable
            notify-send "Tests Failure (${testwatch})" "$(date)" \
                -i /usr/share/icons/gnome/scalable/status/software-update-urgent-symbolic.svg # notification-message-email
        fi

        echo $(($testwatch+1)) >/dev/shm/testwatch;
    fi

    echo -n "${capture}"
}

export -f unit

watch -c -x bash -c unit
