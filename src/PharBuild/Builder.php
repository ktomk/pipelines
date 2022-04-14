<?php

/*
 * pipelines - run bitbucket pipelines wherever they dock
 *
 * Copyright 2017, 2018 Tom Klingenberg <ktomk@github.com>
 *
 * Licensed under GNU Affero General Public License v3.0 or later
 */

namespace Ktomk\Pipelines\PharBuild;

use DateTime;
use Ktomk\Pipelines\Glob;
use Ktomk\Pipelines\Lib;
use Phar;

class Builder
{
    /**
     * public to allow injection in tests
     *
     * @var null|resource to write errors to (if not set, standard error)
     */
    public $errHandle;

    /**
     * @var string path of the phar file to build
     */
    private $fPhar;

    /**
     * @var array to collect files to build the phar from $localName => $descriptor
     */
    private $files;

    /**
     * @var string
     */
    private $stub;

    /**
     * @var array
     */
    private $errors;

    /**
     * @var int directory depth limit for ** glob pattern
     */
    private $limit;

    /**
     * @var string pre-generated replacement pattern for self::$limit
     */
    private $double;

    /**
     * @var array keep file path (as key) to be unlinked on __destruct() (housekeeping)
     */
    private $unlink = array();

    /**
     * @param string $fphar phar file name
     *
     * @return Builder
     */
    public static function create($fphar)
    {
        umask(022);

        $builder = new self();
        $builder->_ctor($fphar);

        return $builder;
    }

    public function __destruct()
    {
        foreach ($this->unlink as $path => $test) {
            if (file_exists($path) && unlink($path)) {
                unset($this->unlink[$path]);
            }
        }
    }

    /**
     * @param string $file
     *
     * @throws \RuntimeException
     *
     * @return $this
     */
    public function stubfile($file)
    {
        unset($this->stub);

        if (!$buffer = file_get_contents($file)) {
            $this->err(sprintf('error reading stubfile: %s', $file));
        } else {
            $this->stub = $buffer;
        }

        return $this;
    }

    /**
     * set traversal limit for double-dot glob '**'
     *
     * @param int $limit 0 to 16, 0 makes '**' effectively act like '*' for path segments
     *
     * @return $this
     */
    public function limit($limit)
    {
        $limit = (int)min(16, max(0, $limit));
        $this->limit = $limit;
        $this->double = $limit
            ? str_repeat('{', $limit) . str_repeat('*/,}', $limit) . '*'
            : '*';

        return $this;
    }

    /**
     * add files to build the phar archive of
     *
     * @param string|string[] $pattern one or more patterns to add as relative files
     * @param callable $callback [optional] to apply on each file found
     * @param string $directory [optional] where to add from
     * @param string $alias [optional] prefix local names
     *
     * @throws \RuntimeException
     *
     * @return $this|Builder
     */
    public function add($pattern, $callback = null, $directory = null, $alias = null)
    {
        if (null !== $directory) {
            $result = realpath($directory);
            if (false === $result || !is_dir($result)) {
                $this->err(sprintf('invalid directory: %s', $directory));

                return $this;
            }
            $directory = $result . '/';
        }

        if (null !== $alias) {
            $result = trim($alias, '/');
            if ('' === $result) {
                $this->err(sprintf(
                    '%s: ineffective alias: %s',
                    is_array($pattern) ? implode(';', $pattern) : $pattern,
                    $alias
                ));
                $alias = null;
            } else {
                $alias = $result . '/';
            }
        }

        foreach ((array)$pattern as $one) {
            $this->_add($one, $callback, (string)$directory, (string)$alias);
        }

        return $this;
    }

    /**
     * Take a snapshot of the file when added to the build, makes
     * it immune to later content changes.
     *
     * @throws \RuntimeException
     *
     * @return \Closure
     */
    public function snapShot()
    {
        $self = $this;
        $unlink = &$this->unlink;

        return function ($file) use ($self, &$unlink) {
            $source = fopen($file, 'rb');
            if (false === $source) {
                $self->err(sprintf('failed to open for reading: %s', $file));

                return null;
            }

            $target = tmpfile();
            if (false === $target) {
                // @codeCoverageIgnoreStart
                fclose($source);
                $self->err(sprintf('failed to open temp file for writing'));

                return null;
                // @codeCoverageIgnoreEnd
            }

            $meta = stream_get_meta_data($target);
            $snapShotFile = $meta['uri'];

            if (false === (bool)stream_copy_to_stream($source, $target)) {
                // @codeCoverageIgnoreStart
                $self->err(sprintf('stream copy error: %s', $file));
                fclose($source);
                fclose($target);
                unlink($snapShotFile);

                return null;
                // @codeCoverageIgnoreEnd
            }
            fclose($source);

            # preserve file from deletion until later cleanup
            $unlink[$snapShotFile] = $target;

            return array('fil', $snapShotFile);
        };
    }

    /**
     * Drop first line from file when added to the build, e.g.
     * for removing a shebang line.
     *
     * @throws \RuntimeException
     *
     * @return \Closure
     */
    public function dropFirstLine()
    {
        $self = $this;

        return function ($file) use ($self) {
            $lines = file($file);
            if (false === $lines) {
                $self->err(sprintf('error reading file: %s', $file));

                return null;
            }
            array_shift($lines);
            $buffer = implode('', $lines);

            return array('str', $buffer);
        };
    }

    /**
     * String replace on file contents
     *
     * @param string $that
     * @param string $with
     *
     * @return \Closure
     */
    public function replace($that, $with)
    {
        return function ($file) use ($that, $with) {
            $buffer = file_get_contents($file);
            $buffer = strtr($buffer, array($that => $with));

            return array('str', $buffer);
        };
    }

    /**
     * build phar file and optionally invoke it with parameters for
     * a quick smoke test
     *
     * @param string $params [options]
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     *
     * @return $this
     */
    public function build($params = null)
    {
        $file = $this->fPhar;
        $files = $this->files;

        $temp = $this->_tempname('.phar');
        if (false === $temp) {
            // @codeCoverageIgnoreStart
            $this->err('fatal: failed to create tmp phar archive file');

            return $this;
            // @codeCoverageIgnoreEnd
        }

        if (file_exists($file) && !unlink($file)) {
            $this->err(sprintf("could not unlink existing file '%s'", $file));

            return $this;
        }

        if (!Phar::canWrite()) {
            $this->err("phar: writing phar files is disabled by the php.ini setting 'phar.readonly'");
        }

        if (empty($files)) {
            $this->err('no files, add some or do not remove all');
        }

        if (!empty($this->errors)) {
            $this->err('fatal: build has errors, not building');

            return $this;
        }

        $phar = new Phar($temp);
        $phar->startBuffering();

        if (null !== $this->stub) {
            $phar->setStub($this->stub);
        }

        $count = $this->_bfiles($phar, $files);
        if (count($files) !== $count) {
            $this->err(sprintf('only %d of %d files could be added', $count, count($files)));
        }

        $phar->stopBuffering();
        unset($phar); # save file

        if (0 === $count) {
            $this->err('fatal: no files in phar archive, must have at least one');

            return $this;
        }

        copy($temp, $file);

        # chmod +x for lazy ones
        if (!chmod($file, 0775)) {
            // @codeCoverageIgnoreStart
            $this->err('error changing mode to 0775 on phar file');
            // @codeCoverageIgnoreEnd
        }

        # smoke test TODO operate on secondary temp file, execution options
        if (null !== $params) {
            $this->exec(sprintf('./%s %s', $file, $params), $return);
            printf("%s\n", $return);
        }

        return $this;
    }

    /**
     * updates each file's unix timestamps in the phar archive,
     * useful for reproducible builds
     *
     * @param DateTime|int|string $timestamp Date string or DateTime or unix timestamp to use
     *
     * @throws \RuntimeException
     *
     * @return $this
     */
    public function timestamps($timestamp = null)
    {
        $file = $this->fPhar;
        if (!file_exists($file)) {
            $this->err(sprintf('no such file: %s', $file));

            return $this;
        }

        // operating based on UTC, squelches PHP date.timezone errors
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set('UTC');
        }

        require_once __DIR__ . '/Timestamps.php';
        $ts = new Timestamps($file);
        $ts->updateTimestamps($timestamp);
        $ts->save($this->fPhar, Phar::SHA1);

        return $this;
    }

    /**
     * output information about built phar file
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     *
     * @return self
     */
    public function info()
    {
        $filename = $this->fPhar;

        if (!is_file($filename)) {
            $this->err(sprintf('no such file: %s', $filename));

            return $this;
        }

        printf("file.....: %s\n", $filename);
        printf("size.....: %s bytes\n", number_format(filesize($filename), 0, '.', ' '));
        printf("SHA-1....: %s\n", sha1_file($filename));
        printf("SHA-256..: %s\n", hash_file('sha256', $filename));

        $pinfo = new Phar($filename);
        printf("file-ver.: %s\n", $pinfo->getVersion());
        printf("api......: %s\n", $pinfo::apiVersion());
        printf("extension: %s\n", phpversion('phar'));
        printf("php......: %s\n", PHP_VERSION);
        $composer = ($buf = getenv('COMPOSER_BINARY')) ? sprintf('%s -f %s --', defined('PHP_BINARY') ? constant('PHP_BINARY') : 'php', escapeshellarg($buf)) : 'composer';
        printf("composer.: %s\n", exec($composer . ' -n --version 2>/dev/null'));
        printf("uname....: %s\n", php_uname('a'));
        printf("count....: %d file(s)\n", $pinfo->count());
        $sig = $pinfo->getSignature();
        printf("signature: %s %s\n", $sig['hash_type'], $sig['hash']);

        return $this;
    }

    /**
     * remove from collected files based on pattern
     *
     * @param $pattern
     * @param bool $error [optional] with no pattern match
     *
     * @return $this
     */
    public function remove($pattern, $error = true)
    {
        if (empty($this->files)) {
            $this->err(sprintf("can not remove from no files (pattern: '%s')", $pattern));

            return $this;
        }

        require_once __DIR__ . '/../../src/Glob.php';

        $result = array();
        foreach ($this->files as $key => $value) {
            if (!Glob::match($pattern, $key)) {
                $result[$key] = $value;
            }
        }

        if (count($result) === count($this->files)) {
            call_user_func(
                $error ? array($this, 'err') : array($this, 'errOut'),
                sprintf("ineffective removal pattern: '%s'", $pattern)
            );
        } else {
            $this->files = $result;
        }

        return $this;
    }

    /**
     * execute a system command
     *
     * @param string $command
     * @param string $return [by-ref] last line of the output (w/o newline/white space at end)
     * @param-out string $return
     *
     * @throws \RuntimeException
     *
     * @return $this
     */
    public function exec($command, &$return = null)
    {
        $return = exec($command, $output, $status);
        if (0 !== $status) {
            $this->err(sprintf('command failed: %s (exit status: %d)', $command, $status));
        }

        $return = rtrim($return);

        return $this;
    }

    /**
     * Execute a utility written in PHP w/ the current PHP binary automatically
     *
     * @param string $command
     * @param string $return [by-ref]  last line of the output (w/o newline/white space at end)
     *
     * @throws \RuntimeException
     *
     * @return $this
     *
     * @see Builder::exec()
     *
     */
    public function phpExec($command, &$return = null)
    {
        list($utility, $parameters) = preg_split('(\s)', $command, 2) + array(1 => null);
        /** @var string $utility */

        $phpUtility = sprintf(
            '%s -f %s --',
            escapeshellcmd(Lib::phpBinary()),
            is_file($utility) ? $utility : exec(sprintf('which %s', escapeshellarg($utility)), $blank, $status)
        );
        if (isset($status) && 0 !== $status) {
            $this->err(sprintf(
                '%s: unable to resolve "%s", verify the file exists and it is an actual php utility',
                'php command error',
                $utility
            ));

            return $this;
        }

        return $this->exec($phpUtility . ' ' . $parameters, $return);
    }

    /**
     * @return array error messages
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @param string $message
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    public function err($message)
    {
        $this->errors[] = $message;
        $this->errOut($message);
    }

    /**
     * @param string $fphar
     *
     * @return void
     */
    private function _ctor($fphar)
    {
        $this->files = array();
        $this->errors = array();
        $this->limit(9);
        $this->fPhar = $fphar;
    }

    /**
     * add files to build the phar archive from
     *
     * @param string $pattern glob pattern of files to add
     * @param callable $callback [optional] callback to apply on each file found
     * @param string $directory [optional]
     * @param string $alias [optional]
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    private function _add($pattern, $callback = null, $directory = null, $alias = null)
    {
        /** @var string $pwd [optional] previous working directory */
        $pwd = null;

        if (!empty($directory)) {
            // TODO handle errors
            $pwd = getcwd();
            chdir($directory);
        }

        $results = $this->_glob($pattern);
        foreach ($results as $result) {
            if (!is_file($result)) {
                continue;
            }

            $file = $directory . $result;
            $localName = $alias . $result;
            $descriptor = array('fil', $file);

            if (null !== $callback) {
                $descriptor = call_user_func($callback, $file);
                if (!is_array($descriptor) || 2 !== count($descriptor)) {
                    $this->err(sprintf(
                        "%s: invalid callback return for pattern '%s': %s",
                        $result,
                        $pattern,
                        rtrim(var_export($descriptor, true))
                    ));

                    continue;
                }
            }

            $this->files[$localName] = $descriptor;
        }

        if (!empty($directory)) {
            // TODO handle errors
            chdir($pwd);
        }
    }

    /**
     * glob with brace
     *
     * @see Builder::_glob()
     *
     * @param string $glob
     * @param int $flags
     *
     * @throws \RuntimeException
     *
     * @return array|false
     */
    private function _glob_brace($glob, $flags)
    {
        $reservoir = array();
        $globs = Glob::expandBrace($glob);
        foreach ($globs as $globEx) {
            $result = \glob($globEx, $flags);
            if (false === $result) {
                // @codeCoverageIgnoreStart
                $this->err(vsprintf(
                    "glob failure '%s' <- '%s'",
                    array($globEx, $glob)
                ));

                return false;
                // @codeCoverageIgnoreEnd
            }

            $result = preg_replace('(//+)', '/', $result);

            foreach ($result as $file) {
                $reservoir["k{$file}"] = $file;
            }
        }

        return array_values($reservoir);
    }

    /**
     * glob with double dot (**) support
     *
     * @param string $pattern
     *
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     *
     * @return array
     */
    private function _glob($pattern)
    {
        /* enable double-dots (with recursion limit, @see Builder::limit */
        $glob = strtr($pattern, array('\*' => '\*', '**' => '{' . $this->double . '/,}'));

        $result = $this->_glob_brace($glob, GLOB_NOSORT);

        if (false === $result) {
            // @codeCoverageIgnoreStart
            $this->err(vsprintf(
                "glob failure '%s' -> '%s'",
                array($pattern, $glob)
            ));

            return array();
            // @codeCoverageIgnoreEnd
        }
        if (array() === $result) {
            $this->err(sprintf(
                'ineffective pattern: %s',
                $pattern === $glob
                    ? $pattern
                    : sprintf("'%s' -> '%s'", $pattern, $glob)
            ));
        }

        return $result;
    }

    /**
     * build chunks from files (sorted by local name)
     *
     * @param array $files
     *
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     *
     * @return array
     */
    private function _bchunks(array $files)
    {
        ksort($files, SORT_STRING) || $this->err('internal: _bchunks ksort failed');

        $lastType = null;
        $chunks = array();
        $nodes = null;
        foreach ($files as $localName => $descriptor) {
            list($type, $context) = $descriptor;

            if ($type !== $lastType) {
                unset($nodes);
                $nodes = array();
                $chunks[] = array('type' => $type, 'nodes' => &$nodes);
                $lastType = $type;
            }

            switch ($type) {
                case 'fil': # type is: key'ed file is (existing) file with relative path on system
                    if (!is_file($context)) {
                        $this->err(sprintf('%s: not a file: %s', $localName, $context));
                    } else {
                        $nodes[$localName] = $context;
                    }

                    break;
                case 'str': # type is: key'ed file is string contents
                    $nodes[$localName] = $context;

                    break;
                default:
                    throw new \UnexpectedValueException(sprintf('unknown type: %s', $type));
            }
        }
        unset($nodes);

        return $chunks;
    }

    /**
     * create temporary file
     *
     * @param string $suffix [optional]
     *
     * @throws \RuntimeException
     *
     * @return false|string
     */
    private function _tempname($suffix = null)
    {
        $temp = tempnam(sys_get_temp_dir(), 'pharbuild.');
        if (false === $temp) {
            // @codeCoverageIgnoreStart
            $this->err('failed to acquire temp filename');

            return false;
            // @codeCoverageIgnoreEnd
        }

        if (null !== $suffix) {
            unlink($temp);
            $temp .= $suffix;
        }

        $this->unlink[$temp] = 1;

        return $temp;
    }

    /**
     * output message on stderr
     *
     * not counting as error, use $this->err($message) to make $message a build error
     *
     * stderr for Builder is $this->errHandle
     *
     * @param string $message
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    private function errOut($message)
    {
        // fallback to global static: if STDIN is used for PHP
        // process, the default constants aren't ignored.
        if (null === $this->errHandle) {
            $this->errHandle = $this->errHandleFromEnvironment();
        }

        is_resource($this->errHandle) && fprintf($this->errHandle, "%s\n", $message);
    }

    /**
     * @throws \RuntimeException
     *
     * @return resource handle of the system's standard error stream
     */
    private function errHandleFromEnvironment()
    {
        if (defined('STDERR')) {
            // @codeCoverageIgnoreStart
            // explicit: phpunit 6 can not test this code cleanly as it is always
            // not defined in phpt tests due to PHP having STDERR not set when a
            // php file read is STDIN (which is the case for phpt tests for PHP
            // code) so this is a work around as this code is tested w/ phpt.
            $handle = constant('STDERR');
            /**
             * @psalm-suppress TypeDoesNotContainType
             * @psalm-suppress RedundantCondition
             */
            if (false === is_resource($handle)) {
                $message = 'fatal i/o error: failed to acquire stream from STDERR';
                $this->errors[] = $message;

                throw new \RuntimeException($message);
            }
            // @codeCoverageIgnoreEnd
        } else {
            // @codeCoverageIgnoreStart
            // explicit: phpunit 7.5+ can not test this code cleanly as it is
            // a fall-back for a previous phpunit version not having STDERR in
            // phpt tests available (see above)
            $handle = fopen('php://stderr', 'wb');
            if (false === $handle) {
                $message = 'fatal i/o error: failed to open php://stderr';
                $this->errors[] = $message;

                throw new \RuntimeException($message);
            }
            // @codeCoverageIgnoreEnd
        }

        return $handle;
    }

    /**
     * @param Phar $phar
     * @param array $files
     *
     * @throws \RuntimeException
     *
     * @return int number of files (successfully) added to the phar file
     */
    private function _bfiles(Phar $phar, array $files)
    {
        $builders = array(
            'fil' => function (array $nodes) use ($phar) {
                $result = $phar->buildFromIterator(
                    new \ArrayIterator($nodes)
                );

                return count($result);
            },
            'str' => function (array $nodes) use ($phar) {
                $count = 0;
                foreach ($nodes as $localName => $contents) {
                    $phar->addFromString($localName, $contents);
                    $count++;
                }

                return $count;
            },
        );

        $count = 0;
        foreach ($this->_bchunks($files) as $chunk) {
            $count += call_user_func($builders[$chunk['type']], $chunk['nodes']);
        }

        return $count;
    }
}
