#!/usr/bin/env bash
# identify the iframe-worker.js script by checksum comparison
#
# usage: ./lib/build/mkdocs/script/iframe-worker-identify.sh
#
if test "$BASH" = "" || "$BASH" -uc "a=();true \"\${a[@]}\"" 2>/dev/null; then
    set -euo pipefail # Bash 4.4, Zsh
else
    set -eo pipefail # Bash 4.3 and older chokes on empty arrays with set -u.
fi
shopt -s nullglob globstar

subject="iframe-worker"
download_dir="build/$subject-downloads"

mkdir -p "$download_dir"

zero_dot_versions="2.0 1.9 1.8 1.7 1.6 1.5 1.4 1.3 1.2" # 0.1.[01] n/a on unpkg.com (which is CDN sort of for npm)
for v in $zero_dot_versions; do
  file="$download_dir/$subject-0.$v.js"
  url="https://unpkg.com/$subject@0.$v/polyfill/index.js"
  { wget -O "$file" -nc "$url" 2>&1 1>&3 | grep -v "not retrieving" >&2; } 3>&1 || :
  if [[ ! -s "$file" ]]; then
    rm -rf "$file"
  fi
done

echo "prj: $(md5sum lib/build/mkdocs/docs/assets/javascripts/iframe-worker.js)"
for v in $zero_dot_versions; do
  file="$download_dir/$subject-0.$v.js"
  echo "$v: $({ cat -- "$file" | grep -v '//# sourceMappingURL=index.js.map'; } | md5sum)"
done
