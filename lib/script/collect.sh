#!/usr/bin/env bash
# collect phar build artifacts
if test "$BASH" = "" || "$BASH" -uc "a=();true \"\${a[@]}\"" 2>/dev/null; then
    # Bash 4.4, Zsh
    set -euo pipefail
else
    # Bash 4.3 and older chokes on empty arrays with set -u.
    set -eo pipefail
fi
shopt -s nullglob globstar

# process "collect" directory
COLLECT_DIR=${COLLECT_DIR-${1-build/collect}}

cd "$COLLECT_DIR"

# create checksum files
# SHA-256: algo for artifacts verification against build logs
# SHA-512: algo for overall artifacts verification
phar_count=0
if [ -z "$(find . -mindepth 2 -type f -path './phar-build-*/*.phar' -print -quit)" ]; then
  echo "::error::no phars found"
else
  # check (and double-check) all files SHA-256 hashes
  shasum --algorithm 256 phar-build-*/*.phar | tee checksums-sha256
  shasum --algorithm 256 --check checksums-sha256
  # check (and double-check) all files SHA-512 hashes
  shasum --algorithm 512 phar-build-*/*.phar | tee checksums-sha512
  shasum --algorithm 512 --check checksums-sha512
  phar_count="$(wc -l < checksums-sha512)"
fi

# write collected phar-build.log
find . -mindepth 2 -type f -name 'build-pipelines-phar.log' -print | sed 's/^\.\///' | sort | xargs tail -n +1 \
  | tee phar-build.log; echo ""

# unified build properties (multiple values of the same SHA hash denote build failure)
{
  # LOGS
  find . -mindepth 2 -type f -name 'build-pipelines-phar.log' -exec tail -n +2 {} \;

  # SHA-1 ... 512 checksums
  sha=1
  find . -mindepth 2 -type f -name 'pipelines.phar' -exec shasum --algorithm $sha {} \; \
    | cut -d\  -f 1 | sed "s/^/SHA-$sha....: /"
  for sha in 256 384 512; do
    find . -mindepth 2 -type f -name 'pipelines.phar' -exec shasum --algorithm $sha {} \; \
      | cut -d\  -f 1 | sed "s/^/SHA-$sha..: /"
  done
} | sort -u | tee build-properties.log

# verify reproducibility for phar build across all ci builds
sha_mismatch=0
for sha in 256 512; do
  if [ "$(sed -n "/^SHA-$sha..: /p" build-properties.log | wc -l)" -gt 1 ]; then
    echo "::error::SHA-$sha indifferent in phar build"
    (( ++sha_mismatch ))
  fi
done

# clean files if no sha_mismatch to reduce collect artifact size
if [[ $sha_mismatch -eq 0 ]]; then
  if [[ $phar_count -ne 0 ]]; then
    cp -v -- "$(find . -mindepth 2 -type f -name 'pipelines.phar' -print | sort | head -1)" \
      "pipelines-$(git describe --tags --always --first-parent --dirty=+).phar"
    echo "phar: $COLLECT_DIR/pipelines-$(git describe --tags --always --first-parent --dirty=+).phar"
  fi
  find . -mindepth 1 -maxdepth 1 -type d -name 'phar-build-*' -print -exec rm -rf {} \;
fi

if [[ $phar_count -eq 0 ]] || [[ $sha_mismatch -gt 0 ]]; then
  >&2 echo "collection error"
  exit 1
fi
