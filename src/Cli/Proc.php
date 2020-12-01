<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use RuntimeException;

/**
 * Run command non-interactively and capture standard output and error
 */
class Proc
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var null|int
     */
    private $status;

    /**
     * @var null|string[]
     */
    private $buffers;

    /**
     * Proc constructor.
     *
     * @param string $command
     */
    public function __construct($command)
    {
        $this->command = $command;
    }

    /**
     * @throws RuntimeException
     *
     * @return int
     */
    public function run()
    {
        $command = $this->command;

        # do nothing placeholder
        if (':' === $command) {
            $this->buffers['stdout'] = $this->buffers['stderr'] = '';

            return $this->status = 0;
        }

        $descriptors = array(
            0 => array('file', '/dev/null', 'r'), // non-interactive running
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        $process = proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException(sprintf('Failed to open: %s', $command)); // @codeCoverageIgnore
        }

        $this->buffers['stdout'] = stream_get_contents($pipes[1]);
        $this->buffers['stderr'] = stream_get_contents($pipes[2]);

        # consume pipes
        foreach ($descriptors as $number => $descriptor) {
            if ('pipe' === $descriptor[0]) {
                $result = fclose($pipes[$number]);
                unset($result); # intentionally ignore errors (fail safe)
            }
        }

        $status = proc_close($process);

        return $this->status = $status;
    }

    /**
     * @return null|int exit status, null if not yet executed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getStandardOutput()
    {
        return $this->buffers ? $this->buffers['stdout'] : '';
    }

    /**
     * @return string
     */
    public function getStandardError()
    {
        return $this->buffers ? $this->buffers['stderr'] : '';
    }

    /**
     * @param null|int $status
     *
     * @return void
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
