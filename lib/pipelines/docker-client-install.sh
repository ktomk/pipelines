#!/bin/sh
#
# install docker client
#
set -u
IFS="$(printf '\n\t ')"

cd build/store/http-cache || exit 2

package="docker-17.12.0-ce.tgz"

if [ ! -f "${package}" ]; then
    curl -fsSLO "https://download.docker.com/linux/static/stable/x86_64/${package}"
    chmod a+rw "${package}"
fi

tar --strip-components=1 -xvzf "${package}" -C /usr/local/bin
