#!/usr/bin/env php
<?php

/* this file is part of pipelines */

Phar::mapPhar('builder-test.phar');

require 'phar://builder-test.phar/test';

__HALT_COMPILER(); ?>
