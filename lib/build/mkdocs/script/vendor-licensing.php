#!/usr/bin/env php
<?php

/*
 * composer packages license information to markdown
 *
 * obtain license information from composer packages
 * and turn them into a markdown report for the
 * pipelines HTML documentation
 *
 * markdown is written to stdout
 *
 * usage: script/vendor-licensing.php [--dev] [<composer-dir>]
 *
 *   --dev            include require-dev packages
 *   <composer-dir>  path to directory with the composer.json to use
 */

$config['dev'] = false;
$config['composer_dir'] = __DIR__ . '/../../../..';

if (isset($argv[1]) && '--dev' === $argv[1]) {
    $config['dev'] = true;
    array_splice($argv, 1, 1);
}

if (isset($argv[1]) && '' !== $argv[1]) {
    $config['composer_dir'] = $argv[1];
}

$composerFile = $config['composer_dir'] . '/composer.json';
$project = json_decode((string)@file_get_contents($composerFile), true);
if (!is_array($project)) {
    fprintf(STDERR, "fatal: not a composer.json: '%s'\n", $composerFile);
    exit(1);
}
$project += array('require' => array(), 'require-dev' => array());

$require = $project['require'];
$config['dev'] && $require += $project['require-dev'];
$pkgs = packages($require, dirname($composerFile) . '/vendor');

tpl_render_pkgs($pkgs);

// done

/**
 * render packages as markdown
 *
 * @param array $pkgs
 */
function tpl_render_pkgs(array $pkgs)
{
    foreach ($pkgs as $pkg) {
        list($pkg, $lic, $lic_file) = array_values($pkg);
?>
### <?= tpl_func_name_nice($pkg) ?> (<?= tpl_func_spdx_full($lic) ?>)

From the package https://packagist.org/packages/<?= $pkg ?>


```
<?= rtrim(file_get_contents($lic_file)), "\n"; ?>
```

#### SPDX

* Full Name: `<?= tpl_func_spdx_full($lic) ?>`
* Short Identifier: `<?= $lic ?>`
* Reference: <?= tpl_func_spdx_url($lic) ?>


<?php
    }
}

/**
 * SPDX license URL of short identifier
 *
 * @param string $lic short identifier
 *
 * @return string license URL
 */
function tpl_func_spdx_url($lic)
{
    return sprintf('https://spdx.org/licenses/%s.html', $lic);
}

/**
 * SPDX full name of short identifier
 *
 * @param string $lic short identifier
 *
 * @return string full license name
 */
function tpl_func_spdx_full($lic)
{
    $fuller = array(
        'MIT' => 'MIT License',
        'BSD-3-Clause' => 'BSD 3-Clause "New" or "Revised" License',
    );
    if (!isset($fuller[$lic])) {
        throw new RuntimeException(sprintf('Unable to resolve SPDX full license for "%s"', $lic));
    }
    return $fuller[$lic];
}

function tpl_func_name_nice($name)
{
    list($ven, $lib) = explode('/', $name, 2) + array(1 => null);
    return sprintf('%s', ucwords($lib, " \t\r\n\f\v-"));
}

/**
 * parse composer requires packages for licensing
 *
 * @param array $require
 * @param string $vendorFolder
 *
 * @return array packages information regarding licensing
 */
function packages(array $require, $vendorFolder)
{
    $packages = array();

    foreach ($require as $pkg => $ver) {
        $dir = $vendorFolder . '/' . $pkg;
        if (!is_dir($dir)) { // filter non-project packages, e.g. php and extensions
            continue;
        }
        $composer = find_file_recursive($dir, 'composer.json');
        if (null === $composer) {
            throw new UnexpectedValueException(sprintf('Unable to find composer.json in %s', $pkg));
        }
        $package = json_decode(file_get_contents($composer), true);
        $packages[$pkg] = array(
            'pkg' => $pkg,
            'lic' => $package['license'],
            'lic_file' => find_license_file(dirname($composer)),
        );
    }

    return $packages;
}

/**
 * find a file within a folder and all its subfolders
 *
 * @param string $folder
 * @param string $file
 *
 * @return string|null file found or null in case not found
 */
function find_file_recursive($folder, $file)
{
    $folders = array($folder);
    while ($folder = array_pop($folders)) {
        if (is_file($folder . '/' . $file)) {
            return $folder . '/' . $file;
        }
        foreach (array_diff((array)@scandir($folder), array('..', '.')) as $name) {
            if (is_dir($folder . '/' . $name)) {
                $folders[] = $folder . '/' . $name;
            }
        }
    }

    return null;
}

/**
 * find license file in a folder
 *
 * @param string $folder
 *
 * @return string path to license file
 */
function find_license_file($folder)
{
    $files = array(
        'COPYING',
        'LICENSE',
    );

    foreach ($files as $file) {
        if (is_file($folder . '/' . $file)) {
            return $folder . '/' . $file;
        }
    }

    throw new RuntimeException(sprintf('Unable to find license file in "%s"', $folder));
}
