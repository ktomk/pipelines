<?php

/*
 * Code coverage checker. Analyzes a given `clover.xml` report produced
 * by PHPUnit and checks if coverage fits expected ratio.
 *
 * Usage:
 *     php coverage-checker <path-to-clover> [<pass-percentage>]
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 *
 * Thanks @ocramius !
 *
 * @see https://github.com/Ocramius/VersionEyeModule/blob/master/coverage-checker.php
 * @see http://ocramius.github.io/blog/automated-code-coverage-check-for-github-pull-requests-with-travis/
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

list($util, $clover, $percentage) = $argv + array(null, null, 100);

/** @noinspection SimpleXmlLoadFileUsageInspection */
if (!$xml = simplexml_load_file($clover)) {
    fprintf(
        STDERR,
        '%s: Invalid input file provided: %s%s',
        basename($util, '.php'),
        $clover,
        PHP_EOL
    );
    exit(2);
}

$percentage = min(100, max(0, round($percentage, 2)));

$fCoverage = function (SimpleXMLElement $xml) {
    $fMetrics = function ($s) use ($xml) {
        return array_sum(array_map(
            'intval',
            $xml->xpath(".//metrics/@${s}elements")
        ));
    };

    $total = $fMetrics('');
    $checked = $fMetrics('covered');

    return (($total === $checked) ? 1 : $checked / $total) * 100;
};

$coverage = round($fCoverage($xml), 2);
if ($coverage >= $percentage) {
    printf('Code coverage is %.2f%% - OK!%s', $coverage, PHP_EOL);
    exit(0);
}

printf(
    'Code coverage is %.2f%%, which is below the accepted %.2f%%%s',
    $coverage,
    $percentage,
    PHP_EOL
);

$clover = realpath($clover);

$fRelName = function ($file) use ($clover) {
    $file = realpath($file);
    $length = min(strlen($clover), strlen($file));
    while (0 !== strpos($file, substr($clover, 0, $length))) {
        $length--;
    }

    return substr($file, $length);
};

foreach ($xml->xpath('//file') as $file) {
    $coverage = $fCoverage($file);
    if ($coverage < $percentage) {
        printf('  %6.2f: %s%s', $coverage, $fRelName($file['name']), PHP_EOL);
    }
}

exit(1);
