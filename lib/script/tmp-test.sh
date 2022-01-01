#!/usr/bin/env bash
#
# this file is part of pipelines
#
# review tmp folder while running tests
#
# the /tmp folder should not clutter up needlessly when running the pipelines
# testsuite, especially the integration test-suite.
#
# however from time to time this happens unnoticed. this script should support
# detecting and resolving these in a more controlled manner.
#
# exits non-zero if any files of the error profile found, with --snapshot
# takes the profile files out of TMP into a tar, keeping numbered backups.
#
# no output unless files are found.
#
# usage: lib/script/tmp-test.sh [--snapshot]
#
set -euo pipefail # mind the bash version
IFS=$' \n\t'
LC_ALL=C

if ! find --version 2>/dev/null 1>&2; then
  >&2 printf 'cowardly refusing to tmp-test as the find version looks incompatible (needs GNU find)\n'
  exit
fi

TMP_DIR=/tmp

print_empty=1
non_empty_buckets_counter=0
tar_file="pipelines-tmp-snapshot.tar"

#####
# print directory/file summary
#
# number of entries, oldest and newest information and total size
#
# 1: type "d" or "f" (or anything on find -type)
# 2: pattern, e.g. "pipelines-cp.*" (by find -name)
#
print_summary() {
  local type="$1"
  local pattern="$2"
  local dir="${3:-$TMP_DIR}"
  local list
  readarray -d '' list < <(find "$dir" -maxdepth 1 -type "$type" -name "$pattern" -printf "%A+ %P\0")
  if [[ ${#list[@]} -gt 0 ]] || [[ $print_empty -eq 0 ]]; then
    printf '%s(%s): %d\n' "$pattern" "$type" "${#list[@]}"
  fi
  if [[ ${#list[@]} -gt 0 ]]; then
    (( ++non_empty_buckets_counter ))
    printf '    %s\n' "${list[@]}" | sort -u | sed -n '1p;1n;$p'
    printf '    size: %s\n' "$(du -ch "$dir/"$pattern | tail -1)"
  fi
}

if [[ "${1:-}" = "--snapshot" ]]; then
  if ! tar --version | grep GNU >/dev/null; then
    >&2 printf 'cowardly refusing to snapshot as the tar version looks incompatible (needs GNU tar)\n'
    exit 1
  fi
  printf 'tar snapshot "%s" from %s ...\n' "$tar_file" "$TMP_DIR"
  if [[ -f "$tar_file" ]]; then
    cp -vf --backup=numbered "$tar_file" "$tar_file"
    rm "$tar_file"
  fi
  tar -cf - -C "$TMP_DIR" --remove-files -b1 --verbatim-files-from -T <(
    find "$TMP_DIR" -maxdepth 1 \( -false \
      -o -name "pipelines-cp.*" \
      -o -name "pipelines-test-suite.*" \
      -o -name "php*" \
    \) -printf "%P\n"
  ) | tee >(head -c-1k > "$tar_file") | tar -tvf -
  ls -al "$tar_file"
  echo "done."
  exit
fi

# check cp (copying into container)
print_summary "d" "pipelines-cp.*"

# check test-suite (integration tests, directory)
print_summary "d" "pipelines-test-suite.*"

# check php (non-prefixed, may leak from elsewhere, files)
print_summary "f" "php*"

if [[ $non_empty_buckets_counter -gt 0 ]]; then
  >&2 printf 'tmp-test: failed. run %s to clean and archive as "%s".\n' "'$0 --snapshot'" "$tar_file"
  exit 1
fi
