#!/bin/sh
#
# information about the pipelines environment
#
# show infos like (first of all) bitbucket pipelines specific
# environment variables
#
set -eu
IFS="$(printf ' \n\t')"

# variable names and descriptions taken from <https://confluence.atlassian.com/
#     bitbucket/environment-variables-794502608.html>
# additional variables by pipelines and docker
vars="
BITBUCKET_BOOKMARK         For use with Mercurial projects.
BITBUCKET_BRANCH           The branch on which the build was kicked off. \
                           This value is only available on branches. \
                           \
                           Not available for builds against tags, or custom \
                           pipelines.
BITBUCKET_BUILD_NUMBER     The unique identifier for a build. It increments \
                           with each build and can be used to create unique \
                           artifact names.
BITBUCKET_CLONE_DIR        The absolute path of the directory that the \
                           repository is cloned into within the Docker \
                           container.
BITBUCKET_COMMIT           The commit hash of a commit that kicked off the \
                           build.
BITBUCKET_EXIT_CODE        The exit status of the last executed command from \
                           a script. If a script command returns non-zero \
                           it is the last executed command. \
                           Set in the after-script.
BITBUCKET_PR_DESTINATION_BRANCH \
                           The name of the pull request destination branch \
                           in combination with BITBUCKET_BRANCH available on \
                           pull request --trigger pr:<source>:<destination> .\
                           \
                           Only available on a pull request triggered build.
BITBUCKET_REPO_OWNER	     The name of the account in which the repository \
                           lives.
BITBUCKET_REPO_SLUG	       The URL-friendly version of a repository name. For \
                           more information, see What is a slug?.
BITBUCKET_STEP_RUN_NUMBER  By default the number is 1, when a pipeline fails \
                           it can be re-run only for failed steps, the failed \
                           step will then have this number increased each \
                           time.
BITBUCKET_TAG              The tag of a commit that kicked off the build. This \
                           value is only available on tags.\
                           \
                           Not available for builds against branches.
CI	                       Default value is true. Gets set whenever a pipeline \
                           runs.
DOCKER_HOST                docker variable: host Docker client connects to (\
                           defaults to socket; depending on system)
DOCKER_CONTEXT             docker variable: context to use
DOCKER_CONFIG              docker variable: path to configuration
DOCKER_TMPDIR              docker variable: temporary directory
PIPELINES_CONTAINER_NAME   pipelines variable: name of the container that is \
                           running.
PIPELINES_ID               pipelines variable: the id of the pipeline that is \
                           running.
PIPELINES_IDS              pipelines variable: space separated list of md5 \
                           hashes of pipeline ids already running. used to \
                           prevent an endless pipelines recursion (pipelines \
                           inside pipelines)
PIPELINES_PARENT_CONTAINER_NAME \
                           pipelines variable: inception related, set to the \
                           name of the parent container (pipelines run inside \
                           a pipeline, if docker client is available) \
                           otherwise not set.
PIPELINES_PIP_CONTAINER_NAME \
                           pipelines variable: inception related, set to the \
                           initial (first) container name.
PIPELINES_PROJECT_PATH \
                           pipelines variable: inception related, set to the \
                           path of project directory that is the source of the\
                           deployment outside of the container.\
                           used to mount inside a pipeline which's deployment\
                           does not provide any mount point.
USER                       current user name, informative
PWD                        current working directory, informative
HOME                       current home directory, informative
"

print_var() {
  # shellcheck disable=SC2039
  local name=$1
  eval "printf \"%-32s:= %s\\n\" \"${name}\" \"\${${name}-*unset*}\""
}

print_vars() {
  # shellcheck disable=SC2039
  local vars="$1"
  # shellcheck disable=SC2034
  while read -r var description; do
    test ${#var} -gt 0 \
      && print_var "${var}"
  done <<EOF
$vars
EOF

    return 0
}

print_vars "$vars"

>&2 echo "debug: this is on stderr"

echo "directory listing of $(pwd):"
ls -al

echo "user/group/s info: $(id -u) ($(id -nu)) / $(id -g) ($(id -gn)) / $(id -G) ($(id -nG))"
