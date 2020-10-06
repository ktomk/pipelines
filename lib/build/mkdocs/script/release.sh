#!/bin/bash
#
# this file is part of pipelines
#
# release.sh - documentation build script (build local clone revision
# and push to github-pages)
#
# usage: script/release.sh [<options>] <revision>
#
#     -h, --help            show this help message
#     -n, --dry-run         dry run
#     --branch=<branch>     branch to use (default: gh-pages)
#     --tag-prefix=<prefix> prefix vcs tag with a simple word
#                           <prefix>.
#
#     <revision>            revision of release
#
set -euo pipefail
shopt -s nullglob globstar
IFS=$' \n\t'

util_name="$(basename "$0")"

help() {
  sed -ne '/^# usage:/,/^[^#]/{/^#/s/^# \?//p}' "$0"
  exit "${1-0}"
}

error() {
  >&2 printf 'fatal: %s\n' "$1"
  help 1
}

run() {
  if [[ -n $dry_run ]]; then set - printf 'dry-run: "%s"\n' "$*"; fi
  "$@"
}

_a_branch() {
  if [[ ! $1 =~ ^[a-z][-a-z0-9]+$ ]]; then
    error "no branch-name: $(printf '%q' "$1")"
  fi
  branch="$1"
}

_a_git_revision() {
  if ! git rev-parse --verify --quiet "$1" >/dev/null; then
    error "no revision: $(printf '%q' "$1")"
  fi
  revision="$1"
  vcs_ref="$(git rev-parse --verify --quiet "$1")"
}

_a_tag_prefix() {
  if [[ ! $1 =~ ^[a-z]+$ ]]; then
    error "no tag-prefix: $(printf '%q' "$1")"
  fi
  tag_prefix="$1"
}

if ! hash git realpath basename; then
  help 127;
fi

dry_run=
branch=gh-pages
tag_prefix=

while [[ ${#} -gt 0 ]]; do
  case $1 in
    -h|--help) help 0;;
    -n|--dry-run) shift; dry_run=1 ;;
    --branch=*) _a_branch "${1#*=}"; shift;;
    --tag-prefix=*) _a_tag_prefix "${1#*=}"; shift;;
    --) shift; break;;
    -*) error "unknown option: $1";;
    *) break;
  esac
done

if [[ ${#} -eq 0 ]]; then
  error "missing revision"
elif [[ ${#} -gt 1 ]]; then
  error "unknown parameter: $2"
fi

if ! git rev-parse --is-inside-work-tree >/dev/null; then
  error "$(printf 'not inside working-tree: %q' "$(pwd)")"
fi

project="$(git rev-parse --show-cdup)"
build="${project}build"
if [[ ! -d "$build" ]]; then
  error "$(printf 'not a directory: %q' "$build")"
fi
vcs_worktree="$build/revision"
_a_git_revision "${1}"

# checkout project @ revision in $vcs_worktree
rm -rf "$vcs_worktree"
git clone -q --local --no-checkout "${project:-.}" "$vcs_worktree"
if ! git -C "$vcs_worktree" checkout -q "$vcs_ref"; then
  error "$(printf 'no checkout revision: %q' "$revision")"
fi

# make html-docs (static) from revision
printf '%s: docs build from source: %s\n' "$util_name" "$vcs_worktree"
vcs_short_ref="$(git -C "$vcs_worktree" rev-parse --short HEAD)"
vcs_tag="${tag_prefix:-}${tag_prefix:+-}$branch-$vcs_short_ref-$revision"

printf '%s: vcs tag: %s\n' "$util_name" "$vcs_tag"
printf '%s: docs %s for %s as branch %s ...\n' "$util_name" "$vcs_short_ref" "$revision" "$branch"

# check tag
if git rev-parse "$vcs_tag" >/dev/null 2>&1 ; then
  error "already tagged: '$vcs_tag'"
fi

# exports for make
source="$vcs_worktree"
export source
export branch

make gh-pages
run make pub-gh-pages

printf "%s: tag '%s' on '%s'\n" "$util_name" "$vcs_tag" "$branch"
run git tag "$vcs_tag" "$branch"
