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
use Ktomk\Pipelines\File\BbplMatch;
use Ktomk\Pipelines\Lib;
use Phar;

class Builder
{
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
     * @var array
     */
    private $deps;

    /**
     * @var array keep file path (as key) do be unlinked on __destruct() (housekeeping)
     */
    private $unlink;

    /**
     * @param string $fphar phar file name
     * @return Builder
     */
    public static function create($fphar)
    {
        $builder = new self();
        $builder->_ctor($fphar);

        return $builder;
    }

    private function _ctor($fphar)
    {
        $this->files = array();
        $this->errors = array();
        $this->limit(9);
        $this->fPhar = $fphar;
    }

    /**
     * @param string $file
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
     * @param int $limit 0 to 16, 0 makes '**' effectively act like '*'
     * @return $this
     */
    public function limit($limit)
    {
        $limit = (int)min(16, max(0, $limit));
        $this->limit = $limit;
        $this->double = $limit
            ? str_repeat('{*/,', $limit) . str_repeat('}', $limit) . '*'
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
     * @return $this|Builder
     */
    public function add($pattern, $callback = null, $directory = null, $alias = null)
    {
        if (null !== $directory) {
            $result = realpath($directory);
            if ($result === false || !is_dir($result)) {
                $this->err(sprintf('invalid directory: %s', $directory));
                return $this;
            }
            $directory = $result . '/';
        }

        if (null !== $alias) {
            $result = trim($alias, '/');
            if ($result === '') {
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
            $this->_add($one, $callback, "$directory", "$alias");
        }

        return $this;
    }

    /**
     * Take a snapshot of the file when added to the build, makes
     * it immune to later content changes.
     *
     * @return \Closure
     */
    public function snapShot()
    {
        return function ($file) {
            $source = fopen($file, 'r');
            if (false === $source) {
                $this->err(sprintf('failed to open for reading: %s', $file));
                return null;
            }

            $target = tmpfile();
            if (false === $target) {
                // @codeCoverageIgnoreStart
                fclose($source);
                $this->err(sprintf('failed to open temp file for writing'));
                return null;
                // @codeCoverageIgnoreEnd
            }

            stream_copy_to_stream($source, $target) || $this->err(sprintf('stream copy error: %s', $file));
            fclose($source);

            $meta = stream_get_meta_data($target);
            $snapShotFile = $meta['uri'];
            $this->unlink[$snapShotFile] = $target; # (preserve file from deletion)

            return array('fil', $snapShotFile);
        };
    }

    /**
     * Drop first line from file when added to the build, e.g.
     * for removing a shebang line.
     *
     * @return \Closure
     */
    public function dropFirstLine()
    {
        return function ($file) {
            $lines = file($file);
            if (false === $lines) {
                $this->err(sprintf('error reading file: %s', $file));
                return null;
            }
            array_shift($lines);
            $buffer = implode("", $lines);

            return array('str', $buffer);
        };
    }

    /**
     * String replace on file contents
     *
     * @param string $that
     * @param string $with
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
     * add files to build the phar archive from
     *
     * @param string $pattern glob pattern of files to add
     * @param callable $callback [optional] callback to apply on each file found
     * @param string $directory [optional]
     * @param string $alias [optional]
     */
    private function _add($pattern, $callback = null, $directory = null, $alias = null)
    {
        /** @var string $pwd [optional] previous working directory */
        $pwd = null;

        if (strlen($directory)) {
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
                if (!is_array($descriptor) || count($descriptor) !== 2) {
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

        if (strlen($directory)) {
            // TODO handle errors
            chdir($pwd);
        }
    }

    /**
     * @see Builder::_glob()
     *
     * @param $glob
     * @param $flags
     * @return array|bool
     */
    private function _glob_brace($glob, $flags)
    {
        $reservoir = array();
        $globs = Lib::expandBrace($glob);
        foreach ($globs as $globEx) {
            $result = \glob($globEx, $flags);
            if ($result === false) {
                // @codeCoverageIgnoreStart
                $this->err(vsprintf(
                    "glob failure '%s' <- '%s'",
                    array($globEx, $glob)
                ));
                return false;
                // @codeCoverageIgnoreEnd
            }

            foreach ($result as $file) {
                $reservoir["k{$file}"] = $file;
            }
        }

        return array_values($reservoir);
    }

    /**
     * globbing with double dot (**) support
     * @param $pattern
     * @return array
     */
    private function _glob($pattern)
    {
        /* enable double-dots (with recursion limit, @see Builder::limit */
        $glob = strtr($pattern, array('\*' => '\*', '**' => $this->double));

        $result = $this->_glob_brace($glob, GLOB_NOSORT);

        if ($result === false) {
            // @codeCoverageIgnoreStart
            $this->err(vsprintf(
                "glob failure '%s' -> '%s'",
                array($pattern, $glob)
            ));
            return array();
            // @codeCoverageIgnoreEnd
        }
        if ($result === array()) $this->err(sprintf(
            "ineffective pattern: %s",
            $pattern === $glob
                ? $pattern
                : sprintf("'%s' -> '%s'", $pattern, $glob)
        ));
        if (!is_array($result)) {
            // @codeCoverageIgnoreStart
            throw new \UnexpectedValueException(
                sprintf('glob: return value not an array: %s', var_export($result, true))
            );
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }

    /**
     * add dependency from vendor
     * @param $name
     * @return $this
     * @deprecated is over-aged for current build, not much need of dependency
     *             management and it would be better that composer fixes auto-
     *             load dumping.
     * @codeCoverageIgnore
     */
    public function dep($name)
    {
        # TODO allow ; in patterns (better than array perhaps even) and allow !pattern)
        $trim = trim($name, '/');
        $prefix = "vendor/$trim/";
        $pattern = sprintf('%s**', $prefix);
        $this->deps[$trim] = 1;
        $this->add($pattern);

        # so far no deep dependencies to include, keeping for future
        # $composer = json_decode(file_get_contents("${prefix}composer.json"), true);
        # print_r($composer['require']);

        return $this;
    }

    /**
     * build phar file and optionally invoke it with parameters for
     * a quick smoke test
     *
     * @param string $params [options]
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

        if ($count === 0) {
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
        if ($params !== null) {
            $this->exec(sprintf('./%s %s', $file, $params), $return);
            printf("%s\n", $return);
        }

        return $this;
    }

    /**
     * updates each file's unix timestamps in the phar archive,
     * useful for reproducible builds
     *
     * @param int|DateTime|string $timestamp Date string or DateTime or unix timestamp to use
     * @return $this
     */
    public function timestamps($timestamp = null)
    {
        $file = $this->fPhar;
        if (!file_exists($file)) {
            $this->err(sprintf('no such file: %s', $file));
            return $this;
        }
        require_once __DIR__ . '/Timestamps.php';
        $ts = new Timestamps($file);
        $ts->updateTimestamps($timestamp);
        $ts->save($this->fPhar, Phar::SHA1);

        return $this;
    }

    /**
     * output information about built phar file
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

        $pinfo = new \Phar($filename);
        printf("count....: %d file(s)\n", $pinfo->count());
        $sig = $pinfo->getSignature();
        printf("signature: %s %s\n", $sig['hash_type'], $sig['hash']);

        return $this;
    }

    /**
     * remove from collected files based on pattern
     *
     * @param $pattern
     * @return $this
     */
    public function remove($pattern)
    {
        if (empty($this->files)) {
            $this->err(sprintf("can not remove from no files (pattern: '%s')", $pattern));
            return $this;
        }

        require_once __DIR__ . '/../../src/File/BbplMatch.php';

        $result = array();
        foreach ($this->files as $key => $value) {
            if (!BbplMatch::match($pattern, $key)) {
                $result[$key] = $value;
            }
        }

        if (count($result) === count($this->files)) {
            $this->err(sprintf("ineffective removal pattern: '%s'", $pattern));
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
     * @return $this
     */
    public function exec($command, &$return = null)
    {
        $return = exec($command, $output, $status);
        if ($status !== 0) {
            $this->err(sprintf('command failed: %s (exit status: %d)', $command, $status));
        }

        $return = rtrim($return);

        return $this;
    }

    /**
     * build chunks from files (sorted by local name)
     *
     * @param array $files
     * @return array
     */
    private function _bchunks(array $files)
    {
        ksort($files, SORT_STRING) || $this->err();

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
                    throw new \UnexpectedValueException(sprintf("unknown type: %s", $type));
            }
        }
        unset($nodes);

        return $chunks;
    }

    /**
     * create temporary file
     *
     * @param string $suffix [optional]
     * @return bool|string
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
     * @return array error messages
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * public to allow injection in tests
     *
     * @var null|resource to write errors to (if not set, standard error)
     */
    public $errHandle;

    /**
     * @param string $message [optional]
     */
    private function err($message = null)
    {
        // fallback to global static: if STDIN is used for PHP
        // process, the default constants aren't ignored.
        if (null === $this->errHandle) {
            $this->errHandle = $this->errHandleFromEnvironment();
        }

        $this->errors[] = $message;
        is_resource($this->errHandle) && fprintf($this->errHandle, "%s\n", $message);
    }

    private function errHandleFromEnvironment()
    {
        if (defined('STDERR')) {
            // @codeCoverageIgnoreStart
            // phpunit can't tests this cleanly as it is always not defined in
            // phpt tests
            $handle = constant('STDERR');
            if (false === is_resource($handle)) {
                $message = 'fatal i/o error: failed to acquire stream from STDERR';
                $this->errors[] = $message;
                throw new \RuntimeException($message);
            }
            // @codeCoverageIgnoreEnd
        } else {
            $handle = fopen('php://stderr', 'w');
            if (false === $handle) {
                // @codeCoverageIgnoreStart
                $message = 'fatal i/o error: failed to open php://stderr';
                $this->errors[] = $message;
                throw new \RuntimeException($message);
                // @codeCoverageIgnoreEnd
            }
        }

        return $handle;
    }

    public function __destruct()
    {
        foreach ((array)$this->unlink as $path => $test) {
            if (file_exists($path) && unlink($path)) {
                unset($this->unlink[$path]);
            }
        }
    }

    /**
     * @param Phar $phar
     * @param array $files
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
