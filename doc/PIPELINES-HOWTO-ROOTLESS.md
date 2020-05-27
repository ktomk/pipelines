# How-To Rootless Pipelines

The pipelines utility runs the pipelines with Docker. [Rootless] \[ROOTLESS] mode was
introduced in Docker Engine 19.03.

If the local user has Docker running in rootless mode, it is possible to
run pipelines rootless. This protects the system the user operates the
pipelines utility on.

This How-To describes how to install docker rootless on Ubuntu 18.04 LTS
(standard procedure) and how to run the pipelines utility with it.

## Installing Docker Rootless

1.  If Docker is installed as daemon (standard), stop it:

    ```
    # systemctl stop docker
    ```

    This should be the only command that needs to be executed as root.

2.  Read the installation instructions by reading the source code of the
    docker rootless installer:

    ```bash
    $ curl -fsSL https://get.docker.com/rootless -o get-docker.sh
    $ less get-docker.sh
    ```

3.  Install docker rootless following the scripted instructions:

    ```bash
    $ sh get-docker.sh
    ```

    The script should run through so that there should be no manual
    intervention necessary. At the very end the script displays the
    DOCKER_HOST environment parameter with it's value and how to
    export it to the environment like this:

    ```bash
    $ export DOCKER_HOST=unix:///run/user/1000/docker.sock
    ```

    It also shows which commands to run to start Docker rootless:

    ```text
    systemctl --user (start|stop|restart) docker
    ```

4.  Prepare the environment to run pipelines with Docker rootless:

    1. If the `DOCKER_HOST` environment parameter is not set, export it to
    the environment first:

    ```bash
    $ export DOCKER_HOST="unix://${XDG_RUNTIME_DIR}/docker.sock"
    ```

    This environment parameter is necessary so that the Docker client
    knows how to connect to the Docker rootless daemon.

    2. Start the Docker rootless daemon if not yet started:

    ```bash
    $ systemctl --user start docker
    ```

    Use `status` instead of `start` to see if and how the daemon is running.

    Use `stop` to stop it again.

5.  Test pipelines to run with Docker rootless (done in the pipelines project
    itself)
    Easiest and fastest is to run the default pipeline w/ mount as it does
    not change anything in the project:

    ```bash
    $ pipelines --deploy mount
    ```

    If the installation of Docker rootless is incomplete, you will see the
    pipelines utility to complain about setting up the container providing
    more info that docker has issues connecting to the Docker daemon like so:

    > ```bash
    > pipelines: setting up the container failed
    > docker: Cannot connect to the Docker daemon at unix:///var/run/docker.sock. Is the docker daemon running?.
    > See 'docker run --help'.
    >
    >
    > exit status: 125
    > ```

   This means either the `DOCKER_HOST` environment parameter is missing or
   not pointing to the correct socket *or* the Daemon is not running. The
   address of the Docker daemon in the error message is useful to review to
   learn about the connection issue.

## How To Progress

Switching the Docker daemon to rootless needs Docker to pull images again as
they are stored in the users home folder (e.g. `~/.local/share/docker`). See
[**Ready for Offline** in Read Me](../README.md#ready-for-offline) on how to
pull pipeline images in batches.

To switch back from rootless to system:

```bash
$ systemctl --user stop docker; sudo systemctl start docker \
  && export DOCKER_HOST="unix:///var/run/docker.sock"
```
and back to rootless:
```bash
$ sudo systemctl stop docker; systemctl --user start docker \
  && export DOCKER_HOST="unix://${XDG_RUNTIME_DIR}/docker.sock"
```

To better integrate with your own users' environment, one could

* Run Docker rootless on login
* Add environment parameter to bashrc / zsh / shell profile

depending on preference.

This is covered in [Rootless] \[ROOTLESS] as well.

## References

* \[ROOTLESS]: https://docs.docker.com/engine/security/rootless/

[Rootless]: https://docs.docker.com/engine/security/rootless/
