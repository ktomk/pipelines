<?php

/*
 * this file is part of pipelines
 *
 * Copyright (c) 2017-2019 Tom Klingenberg <ktomk@github.com>
 *
 * terms specific to this file (the "this software and associated
 * documentation files"):
 *
 * Copyright (c) 2015 Jordi Boggiano
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Ktomk\Pipelines\PharBuild;

class Timestamps
{
    private $contents;

    /**
     * @param string $file path to the phar file to use
     */
    public function __construct($file)
    {
        $this->contents = file_get_contents($file);
    }

    /**
     * Updates each file's unix timestamps in the PHAR
     *
     * The PHAR signature can then be produced in a reproducible manner.
     *
     * @param int|\DateTime|string|bool $timestamp Date string or DateTime or unix timestamp to use
     *
     * @throws \LogicException
     * @throws \RuntimeException
     *
     * @return void
     */
    public function updateTimestamps($timestamp = null)
    {
        if ($timestamp instanceof \DateTime) {
            $timestamp = $timestamp->getTimestamp();
        } elseif (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        } elseif (!is_int($timestamp)) {
            $timestamp = strtotime('1984-12-24T00:00:00Z');
        }

        // detect manifest offset / end of stub
        if (!preg_match('{__HALT_COMPILER\(\);(?: +\?>)?\r?\n}', $this->contents, $match, PREG_OFFSET_CAPTURE)) {
            throw new \RuntimeException('Could not detect the stub\'s end in the phar'); // @codeCoverageIgnore
        }

        // set starting position and skip past manifest length
        $pos = $match[0][1] + strlen($match[0][0]);
        $stubEnd = $pos + $this->readUint($pos, 4);
        $pos += 4;

        $numFiles = $this->readUint($pos, 4);
        $pos += 4;

        // skip API version (YOLO)
        $pos += 2;

        // skip PHAR flags
        $pos += 4;

        $aliasLength = $this->readUint($pos, 4);
        $pos += 4 + $aliasLength;

        $metadataLength = $this->readUint($pos, 4);
        $pos += 4 + $metadataLength;

        while ($pos < $stubEnd) {
            $filenameLength = $this->readUint($pos, 4);
            $pos += 4 + $filenameLength;

            // skip filesize
            $pos += 4;

            // update timestamp to a fixed value
            $this->contents = substr_replace($this->contents, pack('L', $timestamp), $pos, 4);

            // skip timestamp, compressed file size and crc32 checksum
            $pos += 3*4;

            // update or skip file flags - see Bug #77022, use 0644 over 0666
            //                           - see Bug #79082, use 0644 over 0664
            $fileFlags = $this->readUint($pos, 4);
            $permission = $fileFlags & 0x000001FF;
            if ($permission !== 0644) {
                // @codeCoverageIgnoreStart
                $permission = 0644;
                $compression = $fileFlags & 0xFFFFF000;
                $this->contents = substr_replace($this->contents, pack('L', $permission | $compression), $pos, 4);
                // @codeCoverageIgnoreEnd
            }
            $pos += 4;

            $metadataLength = $this->readUint($pos, 4);
            $pos += 4 + $metadataLength;

            $numFiles--;
        }

        if ($numFiles !== 0) {
            throw new \LogicException('All files were not processed, something must have gone wrong'); // @codeCoverageIgnore
        }
    }

    /**
     * Saves the updated phar file, optionally with an updated signature.
     *
     * @param  string $path
     * @param  int $signatureAlgo One of Phar::MD5, Phar::SHA1, Phar::SHA256 or Phar::SHA512
     * @return bool
     * @throws \UnexpectedValueException
     */
    public function save($path, $signatureAlgo)
    {
        $pos = $this->determineSignatureBegin();

        $algos = array(
            \Phar::MD5 => 'md5',
            \Phar::SHA1 => 'sha1',
            \Phar::SHA256 => 'sha256',
            \Phar::SHA512 => 'sha512',
        );

        if (!isset($algos[$signatureAlgo])) {
            throw new \UnexpectedValueException('Invalid hash algorithm given: '.$signatureAlgo.' expected one of Phar::MD5, Phar::SHA1, Phar::SHA256 or Phar::SHA512'); // @codeCoverageIgnore
        }
        $algo = $algos[$signatureAlgo];

        // re-sign phar
        //           signature
        $signature = hash($algo, substr($this->contents, 0, $pos), true)
            // sig type
            . pack('L', $signatureAlgo)
            // ohai Greg & Marcus
            . 'GBMB';

        $this->contents = substr($this->contents, 0, $pos) . $signature;

        return (bool) file_put_contents($path, $this->contents);
    }

    /**
     * @param $pos
     * @param int $bytes
     *
     * @return mixed
     */
    private function readUint($pos, $bytes)
    {
        $res = /** @scrutinizer ignore-call */ unpack('V', substr($this->contents, $pos, $bytes));

        return $res[1];
    }

    /**
     * Determine the beginning of the signature.
     *
     * @return int
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function determineSignatureBegin()
    {
        // detect signature position
        if (!preg_match('{__HALT_COMPILER\(\);(?: +\?>)?\r?\n}', $this->contents, $match, PREG_OFFSET_CAPTURE)) {
            throw new \RuntimeException('Could not detect the stub\'s end in the phar'); // @codeCoverageIgnore
        }

        // set starting position and skip past manifest length
        $pos = $match[0][1] + strlen($match[0][0]);
        $manifestEnd = $pos + 4 + $this->readUint($pos, 4);

        $pos += 4;
        $numFiles = $this->readUint($pos, 4);

        $pos += 4;

        // skip API version (YOLO)
        $pos += 2;

        // skip PHAR flags
        $pos += 4;

        $aliasLength = $this->readUint($pos, 4);
        $pos += 4 + $aliasLength;

        $metadataLength = $this->readUint($pos, 4);
        $pos += 4 + $metadataLength;

        $compressedSizes = 0;
        while (($numFiles > 0) && ($pos < $manifestEnd - 24)) {
            $filenameLength = $this->readUint($pos, 4);
            $pos += 4 + $filenameLength;

            // skip filesize and timestamp
            $pos += 2*4;

            $compressedSizes += $this->readUint($pos, 4);
            // skip compressed file size, crc32 checksum and file flags
            $pos += 3*4;

            $metadataLength = $this->readUint($pos, 4);
            $pos += 4 + $metadataLength;

            $numFiles--;
        }

        if ($numFiles !== 0) {
            throw new \LogicException('All files were not processed, something must have gone wrong'); // @codeCoverageIgnore
        }

        return $manifestEnd + $compressedSizes;
    }
}
