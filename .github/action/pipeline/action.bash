core_startGroup() { printf "::group::%s\n" "$1"; }
core_endGroup() { printf "::endgroup::\n"; }
core_exec() { printf "\e[34m"; printf "%q " "$@"; printf "\e[0m\n"; "$@"; }
