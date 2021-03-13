# Pipelines Environment Variable Usage

Environment variables (also known as environment parameters) are a first
grade citizen of the `pipelines` utility.

The `pipelines` utility has support for Bitbucket Cloud Pipelines
Plugin environment variables. This document describes along the
[list of default variables][BBPL-ENV] \[BBPL-ENV] the level of
support and provides useful information for setting and using
these variables.

This document is a lengthier review of all variables at the time
of writing and provides additional information next to the short
introduction in the [read me](../README.md#environment), both for
limitations of the `pipelines` utility to this regard (and therefore
feature planning) *and* usage guidelines for "dot env" environment
files in project scope in a suggested (and best-intend compatible)
fashion (façon).

## Default Variables

| Variable Name                           | Remarks                    |
| --------------------------------------- |----------------------------|
| `CI`                                    | *all* options; always set to "`true`" |
| `BITBUCKET_BOOKMARK`                    | `--trigger <ref>` where `<ref>` is `bookmark:<name>`; for Mercurial projects |
| `BITBUCKET_BRANCH`                      | `--trigger <ref>` where `<ref>` is `branch:<name>`, `pr:<name>`, `pr:<source>:<destination>`; (source) branch |
| `BITBUCKET_BUILD_NUMBER`                | *all* options; always set to "`0`" |
| `BITBUCKET_CLONE_DIR`                   | *all* options; set by pipelines, it is the deploy directory inside the container (not clone directory) as pipelines has more options than cloning (it currently actually never clones) |
| `BITBUCKET_COMMIT`                      | *all* options; always set to "`0000000000000000000000000000000000000000`" |
| `BITBUCKET_DEPLOYMENT_ENVIRONMENT`      | -/-; currently unsupported |
| <nobr>`BITBUCKET_DEPLOYMENT_ENVIRONMENT_UUID`</nobr> | -/-; currently unsupported |
| `BITBUCKET_EXIT_CODE`                   | *all* options; set to the exit status of the `script` for use in the `after-script` |
| `BITBUCKET_GIT_HTTP_ORIGIN`             | -/-; currently unsupported |
| `BITBUCKET_GIT_SSH_ORIGIN`              | -/-; currently unsupported |
| `BITBUCKET_PARALLEL_STEP`               | *all* options; in a parallel step set to zero-based index of the current step in the group, e.g. 0, 1, 2, ... |
| `BITBUCKET_PARALLEL_STEP_COUNT`         | *all* options; in a parallel step set to the total number of steps in the group, e.g. 5. |
| `BITBUCKET_PR_DESTINATION_BRANCH`       | `--trigger <ref>` where `<ref>` is `pr:<source>:<destination>` for the `<destination>` branch (see as well `BITBUCKET_BRANCH`); destination branch |
| `BITBUCKET_PR_ID`                       | -/-; currently unsupported |
| `BITBUCKET_PROJECT_KEY`                 | -/-; currently unsupported |
| `BITBUCKET_PROJECT_UUID`                | -/-; currently unsupported |
| `BITBUCKET_REPO_FULL_NAME`              | -/-; currently unsupported |
| `BITBUCKET_REPO_OWNER`                  | *all* options; always set to current username from environment or if not available "`nobody`" (which might align w/ the Apache httpd project) |
| `BITBUCKET_REPO_OWNER_UUID`             | -/-; currently unsupported |
| `BITBUCKET_REPO_SLUG`                   | *all* options; always set to base name of project directory |
| `BITBUCKET_REPO_UUID`                   | -/-; currently unsupported |
| `BITBUCKET_STEP_RUN_NUMBER`             | *all* options; defaults to "`1`" and is set to "`1`" _after_ the first run step |
| `BITBUCKET_STEP_TRIGGERER_UUID`         | -/-; currently unsupported |
| `BITBUCKET_TAG`                         | `--trigger <ref>` where `<ref>` is `tag:<name>`; Git projects |

## Comments on Support and Usage

All environment variables are supported in the meaning they can
be explicitly passed with any value of the users wish (or
command) into the container. That is even true for those
variables "always" set to a specific value - variables passed
by the user will always override any automatically "always" set
values.

### Unsupported Features and Environment Variables

Some variables yet do not make sense as the pipelines utility
does not support the underlying feature.

For example, the variable `BITBUCKET_DEPLOYMENT_ENVIRONMENT` and
the depployments are not (yet) supported, therefore the variable
`BITBUCKET_DEPLOYMENT_ENVIRONMENT` is not set. Setting it before
call makes not much sense as it would be set for every script,
and not the deployment script only.

*However:* Some variables which are valid per project and which are
marked "currently unsupported" above do make sense to be put into the
auto-loading environment files (`.env` and `.env.dist`). As long
as their values are *not* considered a secret, they can be added
including the values to `.env.dist` which intention is to be committed
in the project repository (see your VCS/SCMs' documentation, e.g. [for
`git` see git-ignore][GIT-IGNORE] \[GIT-IGNORE]).

Otherwise, instead of poisoning your local environment, project secrets
*can* be put into the private `.env` file (or any other `*.env` file via
the `--env-file` option).

This is as long as you consider your local system safe (if your computer
is connected to the internet this might not be the case and might be an
overall short-coming with the Atlassian Bitbucket Cloud Pipelines
Plugin, take care [!] as *Secured Variables* are just *masked* and can
be read by any user who has write access to a Bitbucket repository -
similar to read access on a/your local system).

### Dealing w/ Secrets as Variable Values

For secret environment parameters, put them into the `.env` file only
(or other `.env` files - if at all) which by intention is/are *not* to
be committed in the project repository.

However: *Required* variables (with secrets) *should* be added with
their name *only* to `.env.dist` file which is the project distribution
parameter definition so that it clearly is documented which variables
*are* required (this can be very helpful to document which secrets are
needed and for non-secret what [sane] defaults are).

### Example Configuration of an Environment File

*Example configuration in a git based project with "dot env"
files as they work w/ the pipelines utility:*

The pipelines utility adheres to environment files as by the Docker
project. That is mainly b/c it is highly dependent on the Docker client
utility and the environment file-format in the Docker project ships
with all needed features:

* **environment variables**,
* environment parameter **imports** / **definitions** and
* **comments** .

The following example consists of three files: First a
`.gitignore` file that takes care that no environment files
but `.env.dist` is getting committed to the git repository.

This is necessary as these files might contain secrets (even in
plain text) which *should never* go into the projects history (applies
to `git`-utility managed projects only for `.gitignore` see your VCS
utility if different for similar options).

From `pipelines` perspective, the file intended to be committed is the
`.env.dist` file. It should contain all major variables but no secrets.
This is possible by writing down the *variable (parameter) name*
**only**. One such variable in the example is `AWS_ACCESS_KEY_ID`. The
key-id and the access key are considered a secret in the following
example.

The `.env` file then (can) contain private information if ok
persisted in the local file-system. Different *dot-env* files
can be provided w/ the `--env-file <path>` option of the
`pipelines` (or `docker`) utility.

* `.gitignore` file:
    ```gitignore
    # ...

    # Private environment variable files (dot env)
    /.env*
    !/.env.dist

    # ...
    ```

    *(for more information on the `.gitignore` file format best
    see the [gitignore documentation][GIT-IGNORE] \[GIT-IGNORE])*

* `.env.dist` file:
    ```bash
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
    ```

    *(secrets in this `.env` file are hidden, only their
    variable names are given, e.g. `AWS_SECRET_ACCESS_KEY`)*

* `.env` file:
    ```bash
    # ---
    # Pipeline: AWS ECR
    #
    AWS_REGION=eu-west-1
    AWS_ACCESS_KEY_ID=ETI1ZUOWJ2GO5Q8IDFDC
    AWS_SECRET_ACCESS_KEY=IzuobhF55og9fel6hleyuo4UOA0lUL9+GE2RmH6J
    ```

This example provides the project-wide information which
environment variables are required for it. Additionally the
project-wide default values are provided and the configuration
setup does allow (local) (secret) configuration.

The comment blocks for a section are multi-line and follow an
exemplary format of the first line of the comment block having a
separating three-dash-line and a single (empty) line at the end
after the second line containing a section title so that it is
easy to *diff* between `.env` and `.env.dist` (and other) files.

### Why Multi-Line Comments?

Why multi-line comments? In practice I found that single line comments
are not that practical with text differs for *sections*, actually this
is how this format came to life; when using various `diff`-utilities to
compare these various *dot-env* files.

It is however up to every users discreet own experience(s) and need(s)
on how to introduce sections in dot env files. It is only a subjective
suggestion.

**Note** that there is a single empty line before any other *but* the
first section comment block *if* the first section comment block starts
on the very first line of the file. This is crucial for all *differs*
I've run across.

## Environment Parameters Considered for Distribution

If a project fully relies on Atlassian Bitbucket Cloud Pipelines
Environment (by my reading of their documentation) the following
environment variables and values should be distributed within the
project:

* `BITBUCKET_PROJECT_KEY`
* `BITBUCKET_PROJECT_UUID`
* `BITBUCKET_REPO_FULL_NAME`
* `BITBUCKET_REPO_OWNER`
* `BITBUCKET_REPO_OWNER_UUID`
* `BITBUCKET_REPO_SLUG` - if not matching project base name
* `BITBUCKET_REPO_UUID`

> **Note:** Perhaps UUID values are gained best via the Bitbucket
Cloud REST API on the shell command line.

## References

* \[BBPL-ENV]: https://confluence.atlassian.com/bitbucket/environment-variables-794502608.html
* \[GIT-IGNORE]: https://git-scm.com/docs/gitignore

[BBPL-ENV]: https://confluence.atlassian.com/bitbucket/environment-variables-794502608.html
[GIT-IGNORE]: https://git-scm.com/docs/gitignore
