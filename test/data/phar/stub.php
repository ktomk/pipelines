#!/usr/bin/env php
<?php

/* this file is part of pipelines */

Phar::mapPhar('builder-test.phar');

/** @noinspection PhpIncludeInspection */
require 'phar://builder-test.phar/test';

__HALT_COMPILER(); ?>
