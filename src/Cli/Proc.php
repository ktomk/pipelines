<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

/**
 * Run command non-interactively and capture standard output and error
 */
class Proc
{
    private $command;

    /**
     * @var int|null
     */
    private $status;

    private $buffers;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function run()
    {
        $command = $this->command;

        # do nothing placeholder
        if ($command === ':') {
            $this->buffers['stdout'] = $this->buffers['stderr'] = "";
            return $this->status = 0;
        }

        $descriptors = array(
            0 => array('file', '/dev/null', 'r'), // non-interactive running
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        $process = proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            throw new \RuntimeException(sprintf('Failed to open: %s', $command)); // @codeCoverageIgnore
        }

        $this->buffers['stdout'] = stream_get_contents($pipes[1]);
        $this->buffers['stderr'] = stream_get_contents($pipes[2]);

        # consume pipes
        foreach ($descriptors as $number => $descriptor) {
            if ($descriptor[0] === "pipe") {
                $result = fclose($pipes[$number]);
                unset($result); # intentionally ignore errors (fail safe)
            }
        }

        $status = proc_close($process);

        return $this->status = $status;
    }

    /**
     * @return int|null exit status, null if not yet executed
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getStandardOutput()
    {
        return $this->buffers['stdout'];
    }

    public function getStandardError()
    {
        return $this->buffers['stderr'];
    }

    /**
     * @param int|null $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
