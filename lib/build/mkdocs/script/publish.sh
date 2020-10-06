#!/bin/bash
#
# this file is part of pipelines
#
# publish.sh - wrapper for ghp-import based mkdocs github-pages publishing
#
# usage: script/publish.sh [<options>] [--] <directory>
#
#     -h, --help            show this help message
#     -n, --dry-run         dry run
#     --branch=<branch>     branch to use (default: gh-pages)
#     -d, --remove          delete <branch> before operation
#     --no-remove           no <branch> deletion
#     -D, --remove-all      remove remote ref (push branch with no history,
#                           implies --remove)
#     --push                push to remote (origin)
#     --no-push             no push to remote
#     --work-tree=<path>    path to working tree to obtain revision information
#                           from, e.g. a checkout specific for the build
#
#     <directory>           path to directory to import w/ ghp-import
#
set -euo pipefail
shopt -s nullglob globstar
IFS=$' \n\t'

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

_a_git_worktree() {
  if ! git -C "${1}" rev-parse --is-inside-work-tree >/dev/null; then
    error "$(printf 'no working-tree directory: %q' "${1}")"
  fi
  vcs_worktree="$1"
}

if ! hash git mkdocs; then
  help 127;
fi

dry_run=
branch=gh-pages
remove=1
push=
vcs_worktree=../../..

while [[ ${#} -gt 0 ]]; do
  case $1 in
    -h|--help) help 0;;
    --no-remove) shift; remove= ;;
    -d|--remove) shift; remove=1 ;;
    -D|--remove-all) shift; remove=2 ;;
    --no-push) shift; push= ;;
    --push) shift; push=1 ;;
    -n|--dry-run) shift; dry_run=1 ;;
    --branch=*) _a_branch "${1#*=}"; shift;;
    --work-tree=*) _a_git_worktree "${1#*=}"; shift;;
    --work-tree) _a_git_worktree "${2}"; shift 2;;
    --) shift; break;;
    -*) error "unknown option: $1";;
    *) break;
  esac
done

if [[ ${#} -gt 1 ]]; then
  error "unknown parameter: $2"
fi

directory="${1-../../../build/gh-pages}"

# delete branch
if [[ -n $remove ]]; then
  if ! git show-ref --verify --quiet "refs/heads/$branch"; then
    printf 'branch already deleted: %q\n' "$branch"
  else
    run git --no-pager branch -D "$branch"
  fi
  if [[ $remove -eq 2 ]]; then
    if ! git show-ref --verify --quiet "refs/remotes/origin/$branch"; then
      printf 'remote tracking branch already deleted: %q\n' "$branch"
    else
      run git --no-pager branch -rd "origin/$branch"
    fi
  fi
fi

# get repository info
vcs_ts="$(git -C "$vcs_worktree" --no-pager log -1 --format=%cd --date=unix)"
vcs_date="$vcs_ts +0000"
vcs_short_ref="$(git -C "$vcs_worktree" rev-parse --short HEAD)"
printf 'revision: %s / %(%F %T)T\n' "$vcs_short_ref" "$vcs_ts"

# prepare environment for commit
export GIT_AUTHOR_NAME="Doc Builder"
export GIT_AUTHOR_EMAIL="doc-builder@pipelines"
export GIT_AUTHOR_DATE="$vcs_date"
export GIT_COMMITTER_NAME="$GIT_AUTHOR_NAME"
export GIT_COMMITTER_EMAIL="$GIT_AUTHOR_EMAIL"
export GIT_COMMITTER_DATE="$GIT_AUTHOR_DATE"

# The mkdocs standard message is: "Deployed f4fd5d1 with MkDocs version: 1.1.2"
ver_mkdocs="$(mkdocs --version | grep -o '[1-9]\.[0-9]\+\.[0-9]\+[^ ]*')"
message="Deployed $vcs_short_ref with MkDocs version: $ver_mkdocs

This message is generated.

mkdocs (+python build) dependencies:
$(sed -n '/^## The following requirements were added by pip freeze:/q;s/^/- /;s/==/ /p' mkdocs-install)

"

run ghp-import --message="$message" --branch="$branch" ${push:+--push} --force "$directory"
