<?php

/* this file is part of pipelines */

/**
 * update README.md with the usage/help information from source
 *
 * usage: php -f lib/build/usage.php
 */

$readmePath = __DIR__ . '/../../README.md';

/**
 * @param string $buffer
 * @param string $start
 * @param string $end
 *
 * @return array|false
 */
function text_range($buffer, $start, $end)
{
    $startPos = strpos($buffer, $start);
    if (false === $startPos) {
        return false;
    }

    $startInner = $startPos + strlen($start);

    $endPos = strpos($buffer, $end, $startInner);
    if (false === $endPos) {
        return false;
    }

    $inner = substr($buffer, $startInner, $endPos - $startInner);

    return array($startPos, $startInner, $endPos, $inner);
}

$buffer = file_get_contents($readmePath);

$helpStart = "<!-- help -->\n```\n";
$helpEnd = "```\n";
if (!$helpRange = text_range($buffer, $helpStart, $helpEnd)) {
    fwrite(STDERR, "usage.php: failed to find start/end position of help\n");
    exit(1);
}

$helpBuffer = file_get_contents(__DIR__ . '/../../src/Utility/Help.php');
$usage = text_range($helpBuffer, "<<<'EOD'\n", "\nEOD\n");
if (!$usage) {
    fwrite(STDERR, "usage.php: failed to find start/end position of usage\n");
    exit(1);
}
$help = text_range(substr($helpBuffer, $usage[1]), "<<<'EOD'\n", "\nEOD\n");
if (!$help) {
    fwrite(STDERR, "usage.php: failed to find start/end position of usage (2)\n");
    exit(1);
}
$usage = $usage[3] . $help[3];

$buffer = substr_replace(
    $buffer,
    $usage,
    $helpRange[1],
    $helpRange[2] - $helpRange[1]
);

file_put_contents($readmePath, $buffer);
