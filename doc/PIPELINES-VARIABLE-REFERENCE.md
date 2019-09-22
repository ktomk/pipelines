# Pipelines Environment Variable Usage Reference

Environment variables (sometimes also called environment
parameters) are a first grade citizen of the pipelines utility.

The pipelines utility has support for Bitbucket Cloud Pipelines
Plugin environment variables. This document describes along the
[list of default variables][BBPL-ENV] \[BBPL-ENV] the level of
support and provides useful information for setting and using
these variables.

This document is a lengthier review of all variables at the time
of writing and provides additional information next to the short
introduction in the [read me](../README.md), both for limitations
of the pipelines utility to this regard (and therefore feature
planning) *and* usage guidelines for "dot env" environment files
in project scope in a suggested (and best-intend compatible)
fashion (façon).

## Default Variables

| Variable Name                           | Options       | Comment  |
| --------------------------------------- |---------------| -------- |
| `CI`                                    | *all* | always set to "`true`" |
| `BITBUCKET_BOOKMARK`                    | `--trigger <ref>` where `<ref>` is `bookmark:<name>` | Mercurial projects |
| `BITBUCKET_BRANCH`                      | `--trigger <ref>` where `<ref>` is `branch:<name>` or `pr:<name>` | source branch |
| `BITBUCKET_BUILD_NUMBER`                | *all* | always set to "`0`" |
| `BITBUCKET_CLONE_DIR`                   | *all* | set by pipelines, it is the deploy directory (not clone directory) as pipelines has more options than cloning (it currently actually never clones) |
| `BITBUCKET_COMMIT`                      | *all* | always set to "`0000000000000000000000000000000000000000`" |
| `BITBUCKET_DEPLOYMENT_ENVIRONMENT`      | -/-   | currently unsupported |
| `BITBUCKET_DEPLOYMENT_ENVIRONMENT_UUID` | -/-   | currently unsupported |
| `BITBUCKET_EXIT_CODE`                   | -/-   | currently unsupported |
| `BITBUCKET_GIT_HTTP_ORIGIN`             | -/-   | currently unsupported |
| `BITBUCKET_GIT_SSH_ORIGIN`              | -/-   | currently unsupported |
| `BITBUCKET_PARALLEL_STEP`               | -/-   | currently unsupported |
| `BITBUCKET_PARALLEL_STEP_COUNT`         | -/-   | currently unsupported |
| `BITBUCKET_PR_DESTINATION_BRANCH`       | -/-   | currently unsupported |
| `BITBUCKET_PR_ID`                       | -/-   | currently unsupported |
| `BITBUCKET_PROJECT_KEY`                 | -/-   | currently unsupported |
| `BITBUCKET_PROJECT_UUID`                | -/-   | currently unsupported |
| `BITBUCKET_REPO_FULL_NAME`              | -/-   | currently unsupported |
| `BITBUCKET_REPO_OWNER`                  | *all* | always set to current username from environment or if not available "`nobody`" (which might align w/ the Apache httpd project) |
| `BITBUCKET_REPO_OWNER_UUID`             | -/-   | currently unsupported |
| `BITBUCKET_REPO_SLUG`                   | *all* | always set to base name of project directory |
| `BITBUCKET_REPO_UUID`                   | -/-   | currently unsupported |
| `BITBUCKET_STEP_RUN_NUMBER`             | -/-   | currently unsupported |
| `BITBUCKET_STEP_TRIGGERER_UUID`         | -/-   | currently unsupported |
| `BITBUCKET_TAG`                         | `--trigger <ref>` where `<ref>` is `tag:<name>` | Git projects |

## Comments on Support and Usage

All environment variables are supported in the meaning they can
be explicitly passed with any value of the users wish (or
command) into the container. That is even true for those
variables "always" set to a specific value - variables passed
by the user will always override any automatically "always" set
values.

### Unsupported Features and Environment Variables

Some variables yet do not make sense as the pipelines utility
does not support the underlying feature. For example, parallel
step execution is not (yet) supported, therefore the variables
`BITBUCKET_PARALLEL_STEP` and `BITBUCKET_PARALLEL_STEP_COUNT`
are not set. Setting them before call makes not much sense
as they would be set for every step, not just the parallel ones.

Another example is `BITBUCKET_EXIT_CODE`. Even thought this
variable is easy to be set by the current piplines runner
implementation, there is no such *after-script* handling feature
yet where that variable is of practical use.

However:

Some variables which are valid per project and which are marked
"currently unsupported" above do make sense to be put into the
auto-loading environment files (`.env` and `.env.dist`). As long
as their values are *not* considered a secret, they can be added
to `.env.dist` which's intention is to be committed in the
project repository. Otherwise, instead of poisoning your local
environment, project secrets *can* be put into the private `.env`
file as long as you consider files on your local system safe for
storage (if your computer is connected to the internet this might
not be the case and might be a short-cumming with the overall
Atlassian Bitbucket Cloud Pipelines Plugin anyways).

### Dealing w/ Secrets as Variable Values

For secret values, put them into the `.env` file only (if at all)
which's intention is to *not* be committed in the project
repository.

However:

*Required* variables (with secrets) *should* be added with their
name *only* to `.env.dist` file which is the project distribution
so that it is documented that these variables *are* required
(this can be very helpful to document which secrets are needed).

### Environment File Example Configuration

Example configuration in a git based project with "dot env"
files as they work w/ the pipelines utility.

The pipelines utility adheres to environment files as by the
Docker project. That is mainly b/c it is highly dependent on
the Docker client utility but also the environment file-format
in the Docker project ships with all needed features: variable
definition, variable import and comments.

The following example consists of three files: First a
`.gitignore` file that takes care that no environment files
but `.env.dist` is getting committed to the git repository.

This is necessary as these files might contain secrets (even in
plain text) which *should never* go into the projects history.

The file intended to be committed is the `.env.dist` file. It
should contain all major variables but no secrets. This is
possible by writing down the variable name only. One such
variable in the example is `AWS_ACCESS_KEY_ID`. The key-id and
the access key are considered a secret in the following example.

The `.env` file then (can) contain private information if ok
persisted in the local file-system. Different dot env files
can be provided w/ the `--env-file <path>` option of the
`pipelines` (or `docker`) utility.

* `.gitignore` file:
    ~~~
    # ...

    # Private environment variable files (dot env)
    /.env*
    !/.env.dist

    # ...
    ~~~

    *(for more information on the `.gitignore` file format best
    see the [gitignore documentation][GIT-IGNORE] \[GIT-IGNORE])*

* `.env.dist` file:
    ~~~
    # ---
    # Pipeline: Gitlab Cloud Project
    #
    BITBUCKET_REPO_OWNER=ACME.Inc.
    BITBUCKET_REPO_OWNER_UUID=894510a8-be2a-4660-a276-10fd5280cc61
    BITBUCKET_REPO_SLUG=sudo-make-sandwich
    BITBUCKET_REPO_UUID=894510a8-be2a-4660-a276-10fd5280cc61

    # ---
    # Pipeline: AWS ECR
    #
    AWS_REGION=eu-west-1
    AWS_ACCESS_KEY_ID
    AWS_SECRET_ACCESS_KEY
    ~~~

    *(secrets in this `.env` file are hidden, only their
    variable names are given, e.g. `AWS_SECRET_ACCESS_KEY`)*

* `.env` file:
    ~~~
    # ---
    # Pipeline: AWS ECR
    #
    AWS_REGION=eu-west-1
    AWS_ACCESS_KEY_ID=ETI1ZUOWJ2GO5Q8IDFDC
    AWS_SECRET_ACCESS_KEY=IzuobhF55og9fel6hleyuo4UOA0lUL9+GE2RmH6J
    ~~~

This example provides the project-wide information which
environment variables are required for it. Additionally the
project-wide default values are provided and the configuration
setup does allow (local) (secret) configuration.

The comment blocks for a section are multi-line and follow the
exemplary format of the first line of the comment block having a
separating three-dash-line and a single (empty) line at the end
after the second line containing a section title so that it is
easy to *diff* between `.env` and `.env.dist` (and other) files.

Single line comments are not that practical with text differs for
*sections*, this is how this format came to live. It is however
up to every users discreet own experiences and needs on how to
introduce sections in dot env files.

Keep notice that there is a single empty line before any other
*but* the first section comment block *if* the first section
comment block starts on the very first line of the file.

## Environment Parameters Considered for Distribution

If a project fully relies on Atlassian Bitbucket Cloud Pipelines
Environment (by my reading of their documentation) the following
environment variable and values should be distributed within the
project:

* `BITBUCKET_PROJECT_KEY`
* `BITBUCKET_PROJECT_UUID`
* `BITBUCKET_REPO_FULL_NAME`
* `BITBUCKET_REPO_OWNER`
* `BITBUCKET_REPO_OWNER_UUID`
* `BITBUCKET_REPO_SLUG` - if not matching project base name 
* `BITBUCKET_REPO_UUID`

***Note:** Perhaps UUID values are gained best via the Bitbucket
Cloud REST API on the shell command line.*  

## References

* \[BBPL-ENV]: https://confluence.atlassian.com/bitbucket/environment-variables-794502608.html
* \[GIT-IGNORE]: https://git-scm.com/docs/gitignore

[BBPL-ENV]: https://confluence.atlassian.com/bitbucket/environment-variables-794502608.html
[GIT-IGNORE]: https://git-scm.com/docs/gitignore
