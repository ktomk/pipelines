#!/bin/sh
#
# install docker client
#
set -u
IFS="$(printf '\n\t ')"

cd build/store/http-cache

package="docker-17.03.1-ce.tgz"

if [ ! -f "${package}" ]; then
    curl -fsSLO "https://get.docker.com/builds/Linux/x86_64/${package}"
    chmod a+rw "${package}"
fi

tar --strip-components=1 -xvzf "${package}" -C /usr/local/bin
