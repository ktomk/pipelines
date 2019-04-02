#!/usr/bin/env php
<?php

/*
 * pipelines - run bitbucket pipelines wherever they dock *
 *
 * Copyright 2017-2019 Tom Klingenberg <ktomk@github.com>
 *
 * Licensed under GNU Affero General Public License v3.0 or later
 */

Phar::mapPhar('pipelines.phar');

require 'phar://pipelines.phar/bin/pipelines';

__HALT_COMPILER();
